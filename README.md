# vhost-brute
A PHP tool to brute force vhost configured on a server.  

```
Usage: php vhost-brute.php [OPTIONS]

Options:
	--domain	set domain
	-h, --help	print this help
	--ip		set server ip address
	--port		set port
	--ssl		force ssl
	--threads	set maximum threads, default=1
	--wordlist	set plain text file that contains subdomains to test

Examples:
	php vhost-brute.php --domain=example.com --wordlist=sub.txt --threads=5
```

<img src="https://raw.githubusercontent.com/gwen001/vhost-brute/master/example.jpg" alt="Virtual Host Brute Force example">
<br><br>

I don't believe in license.  
You can do want you want with this program.  

