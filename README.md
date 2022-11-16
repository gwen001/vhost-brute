<h1 align="center">vhost-brute</h1>

<h4 align="center">A PHP tool to brute force vhost configured on a server.</h4>

<p align="center">
    <img src="https://img.shields.io/badge/php-%3E=5.5-blue" alt="php badge">
    <img src="https://img.shields.io/badge/license-MIT-green" alt="MIT license badge">
    <a href="https://twitter.com/intent/tweet?text=https%3a%2f%2fgithub.com%2fgwen001%2fvhost-brute%2f" target="_blank"><img src="https://img.shields.io/twitter/url?style=social&url=https%3A%2F%2Fgithub.com%2Fgwen001%2Fvhost-brute" alt="twitter badge"></a>
</p>

<p align="center">
    <img src="https://img.shields.io/github/stars/gwen001/vhost-brute?style=social" alt="github stars badge">
    <img src="https://img.shields.io/github/watchers/gwen001/vhost-brute?style=social" alt="github watchers badge">
    <img src="https://img.shields.io/github/forks/gwen001/vhost-brute?style=social" alt="github forks badge">
</p>

---

## Install

```
git clone https://github.com/gwen001/vhost-brute
```

## Usage

```
Usage: php vhost-brute.php [OPTIONS]

Options:
	--domain	set domain
	--fail		max fail (http code=0) before exiting, default=-1, unlimited
	-h, --help	print this help
	--ip		set server ip address
	--port		set port
	--ssl		force ssl
	--st		percentage of similarity of the content to NOT confirm, default=90 
			so under 90 it's considered different than the reference
	--threads	set maximum threads, default=1
	--wordlist	set plain text file that contains subdomains to test

Examples:
	php vhost-brute.php --ip xxx.xxx.xxx.xxx --domain example.com --wordlist sub.txt --threads 5
```

---

<img src="https://raw.githubusercontent.com/gwen001/vhost-brute/master/preview.jpg" />

---

Feel free to [open an issue](/../../issues/) if you have any problem with the script.  

