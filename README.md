Make sure you installed apache2 and mysql on the server.
1. Install LAMP on the server
1) Install Apache
	sudo apt update
	sudo apt install apache2
2) Install MySQL
	sudo apt install mysql-server
	sudo mysql_secure_installation
	!!! Set default root password empty when it's asked	
3) Install PHP
	sudo apt install php libapache2-mod-php php-mysql

2. Install DB & Web Backend
1) Install DB
	sudo mysql -u root -p
	Press Enter and it'll show you mysql shell. Run following command to create database.
		CREATE DATABASE IF NOT EXISTS basketballcoach;
2) Install Web Backend
	- Install composer
		sudo apt-get install composer
	- Git clone the backend repository and install required packages
		git clone https://github.com/naldokan/BasketballCoach_Backend
		composer install
	- Configuration of webapp backend
		cd BasketballCoach_Backend
		cp .env.example .env
		change following items in .env file
			DB_DATABASE=basketballcoach
			DB_USERNAME=root
			DB_PASSWORD=
		php artisan key:generate
		php artisan migrate
		check if web backend is working
			php artisan serve
			Go to web browser and visit "localhost:8000" to check if Laravel default page is opening.

	- Deploying web backend as permanent web service using apache2
		sudo nano /etc/apache2/sites-available/laravel.conf
		Write down following content
			<VirtualHost *:80>   
			     ServerAdmin admin@local.basketballcoach.com
			     DocumentRoot PATH_TO_THE_PROJECT_FOLDER/public (e.g, /home/osboxes/Tasks/Basketball/BasketballCoach_Backend/public)
			     ServerName local.basketballcoach.com
	     		     <Directory PATH_TO_THE_PROJECT_FOLDER/public (e.g, /home/osboxes/Tasks/Basketball/BasketballCoach_Backend/public)>
	          	     	Options +FollowSymlinks
			        AllowOverride All
	        		Require all granted
			     </Directory>
	
			     ErrorLog ${APACHE_LOG_DIR}/error.log
			     CustomLog ${APACHE_LOG_DIR}/access.log combined
			</VirtualHost>
		Save file
		sudo a2dissite 000-default.conf
		sudo a2ensite basketballcoach
		sudo a2enmod rewrite
		sudo systemctl restart apache2
		Check if deployment is successful:
			Open web browser and visit ip address of the PC. (e.g, http://192.168.200.69)
			This should open a Laravel default page which was shown in the above
	
			If page shows php code of index.php do following to figure that issue out.
				sudo apt-get install libapache2-mod-php7.2
				sudo service apache2 restart
			If you can't connect to the server from other pcs but can on localhost, try to disable firewall
				sudo ufw disable
