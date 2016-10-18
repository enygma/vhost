## vhost

This is just a simple tool to add a new vhost for local development on a stock OS X system.
There's several hard-coded default paths right now but I might make it more configurable in the future.
Because this requires being able to write to system files and restart Apache, you'll need to run it as a user with the appropriate level of permissions.

### Usage

```
php exec.php [hostname]
```

The script will then:

- Update your `/etc/hosts` file
- Add a new vhost entry to your Apache config
- Optionally make the `/var/www/[hostname]` docroot if it doesn't exist

It also does some basic error checking to be sure you don't accidentally overwrite something that already exists.
