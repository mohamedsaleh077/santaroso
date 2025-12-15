# Santaroso-project
simple imageboard with PHP, try: https://santaroso.ct.ws

this project is beta and not fully tested! use it on your own risk! give me feedbacks in issues to help me improve it if 
you are interested in it or pull request me, project from community for the community!

## What Can this Do?
old school Anonymous Simple Imageboard with PHP and 100% privacy.
## Features
- ~~you can add boards, by this Query:~~
```sql
INSERT INTO boards (name, description) VALUES ('test', 'test board');
```
- users can open threads in the boards.
- users can make comments on the threads.
- threads showen in a gllaery view like pinterest.
- threads and comemnts counter.
- uploading Images/Videos/Audio up to 10MB and generating thumbinals.
- ready to deploy anywhere, just make your own config,ini ez!
- top 9 threads in the home page.
- you can set a custom name in config.ini
- panel for the admin:
  - see visits count, boards, threads and comments
  - create boards and delete them
  - ban IPs and delete content

## Future Plans
- ~~add Admin panel.~~
- ~~admin will make boards, delete content and ban IPs.~~
- logs for everything is going to help troubleshoting and detect unusal behaviors.
- add more customization options for UI.
- ~~add popular threads.~~
- add Archiving threads.
- add lock for threads.
- report system.

I may do this in another project lmao, so this features will be from the community, just cook some code then PR me!

## Security
- CSRF tokens.
- limit user from spam by 60 secound for each action.
- XSS not possible.
- no SQL Injections (modern PDO).
- checking for uploaded files.
- validating text length.
- reCaptcha

## used technologies
- HTML
- CSS
- Java Script
- BootStrap 5.3
- PHP
- MySQL
- OOP
- Docker
- FireFox
- PHPSTORM
- JetBrains AI Agent (Junie)
- Gemini Fast in Canvas mode

## How To host the project?
### what you need?
the project works fine with PHP 8.3.26, MySQL 8.3.26 and Apache 2.4.65.
### how to deploy
- put all files in `website` in your htdocs directory.
- edit config.ini based on your host and API.
- Database schema is located at `db/sd.sql` (idk why sd, it should be db but whatever).
- for home page, go to https://yourdomain 
- for admin panel, go to https://yourdomain/login.php then login with:
  - username: `admin`
  - password: `admin`
  - you can change them when you log in! or insert another admin using:
  ```sql
    INSERT INTO admins (username, password) VALUES ("whatever the name", "dkvjnl.dskvj")
  ```
  
*NOTES*: there is no hashing for the password, and you can change it once you logged in! 

## How To Run it locally?
### setting up docker
- install docker, docker-compose and docker-desktop
- run docker desktop
- run these commands
```bash
    cd ./docker
    docker compose up --build
```

### to access the project (docker)
- for the website: https://localhost
- for phpmyadmin: https://localhost:8081
- for database: `db:3306` and dbname is `santaroso`
- database users: `root:root`, `user:password` yes seriously!

or you can use your own LAMP/WAMP/MAMP/XAMPP, put the files of website in the htdocs, run the SQL code in sd.sql in your
database and edit `/website/config.ini`.