<?php

require_once 'vendor/autoload.php';
use Cmd\Command;
use Cmd\Output;

$cmd = new Command();
$output = new Output();
$output->addType('none', 'white', 'black');
$output->addType('success1', 'green', 'black');

$apacheConfigPath = '/etc/apache2/extra/httpd-vhosts.conf';
$hostsFile = '/etc/hosts';
$port = '80';
$vhostTemplate = '<VirtualHost *:%s>
	ServerName %s
	DocumentRoot %s
	ErrorLog "/var/log/www/%s-error_log"
</VirtualHost>';

// Parse the arguments
$args = $cmd->execute($_SERVER['argv']);

// If not args provided, the --help option is given or no hostname is given...
if (empty($args) || isset($args['help']) || !isset($args[0])) {
    $output->none("Usage example:\n");
    echo './vhost <hostname> [--port=80] [--config=/path/to/config] [--httpConfig=/path/to/apache/config/file] -c';
    echo "\n\n";
    echo $output->none("Default Apache configuration path:\n");
    echo $apacheConfigPath."\n";
}
// Be sure our hosts file is writeable
if (!is_writable($hostsFile)) {
    $output->error('Cannot write to hosts file ('.$hostsFile.'). Are yoyu running as a user with the right permissions?');
    die("\n");
}
$hostname = $args[0];

// Does it have .localhost on it?
if (strstr('.localhost', $hostname) == false) {
    $hostname .= '.localhost';
}

// Be sure our document roo exists
$docroot = '/var/www/'.str_replace('.localhost', '', $hostname);
if (!is_dir($docroot)) {
    if (isset($args['c'])) {
        echo $output->info('Creating document root: '.$docroot)."\n";
        mkdir($docroot);
        file_put_contents($docroot.'/index.php', '<?php echo $_SERVER["HTTP_HOST"]; ?>');
    } else {
        echo $output->error('Document root "'.$docroot.'" does not exist!', true)."\n";
        echo "Use the '-c' option to create it.\n";
        die("\n");
    }
}

// Start with the hosts file - parse it and see if it already exists
$hostsContents = file($hostsFile);
// print_r($hostsContents);
foreach ($hostsContents as $line) {
    if (empty($line)) {
        continue;
    }
    preg_match('/[0-9\.]+[\s\t]+(.+)/', $line, $matches);

    if (isset($matches[1]) && $matches[1] == $hostname) {
        echo $output->error('Hostname "'.$hostname.'" already exists in host file!', true)."\n";
        echo $line;
        die("\n");
    }
}
// If it's not there, go add it
$hostsContents[] = '127.0.0.1   '.$hostname;
file_put_contents($hostsFile, implode("", $hostsContents));
echo $output->success1('Hosts file updated...')."\n";

$vhost = vsprintf($vhostTemplate, [
    $port,
    $hostname,
    $docroot,
    str_replace('.localhost', '', $hostname)
]);

echo $output->info('Adding vhost to apache: '.$hostname, true)."\n";
// Add the vhost to the file
file_put_contents($apacheConfigPath, $vhost."\n", FILE_APPEND);

// Restart apache to make changes work
exec('sudo /usr/sbin/apachectl restart');

echo $output->success1('Success!', true)."\n";
echo 'You can view this new vhost here: http://'.$hostname.':'.$port."\n";

echo "\n";
