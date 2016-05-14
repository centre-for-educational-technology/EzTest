# EduSystem

A lightweight student testing system.

## Requirements

- Modern PHP, versions 5.6 and up
- [Composer](https://getcomposer.org/)
- MySQL database
- Web server, such as apache or nginx
- Configured sendmail daemon or SMTP server to send emails to students

## Installation

### 1. Install dependancies

Run `composer install` command in the root folder of the project.

### 2. Import the database scheme

It is recommended to create a new locked down user and a new database for use with this system.
After that is done, import the `install/database.sql` file into your database.

### 3. Edit the configuration

Edit `src/Config.php` file to configure database and email settings.
This configuration file straight forward and contains most basic settings, such as
public system name, database and mail settings.

### 4. Configure the web server

`www` folder must be the only folder that can be accessed over the web. Protect everything else.

#### Apache
Make sure `AllowOverride` is on for your directory, or put `www/.htaccess` rules in `httpd.conf`

#### nginx
```nginx
location / {
	try_files $uri $uri/ /index.php?$args;
}
```
