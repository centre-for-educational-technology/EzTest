# EduSystem
My final project!


## Installation

### Web server

#### Apache
Make sure `AllowOverride` is on for your directory, or put `www/.htaccess` rules in `httpd.conf`

#### nginx
```nginx
location / {
	try_files $uri $uri/ /index.php?$args;
}
```

### Edit configuration

Edit `src/Config.php` file to configure database and email settings.
