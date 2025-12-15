# Santaroso-project
simple imageboard with PHP, try: https://santaroso.ct.ws

## What Can this Do?
old school Anonymous Simple Imageboard with PHP and 100% privacy.
## Features
- you can add boards, by this Query:
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

## Future Plans
- add Admin panel.
- admin will make boards, delete content and ban IPs.
- logs for everything is going to help troubleshoting and detect unusal behaviors.
- add more customization options for UI.
- ~~add popular threads.~~
- add Archiving threads.
- add lock for threads.
- report system.
I may do this in another project lmao

## Security
- CSRF tokens.
- limit user from spam by 60 secound for each action.
- XSS not possible.
- no SQL Injections (modern PDO).
- checking for uploaded files.
- validating text length.
- no data is being collected.

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

## How To Run?
### setting up docker
- install docker, docker-compose and docker-desktop
- run docker desktop
- run these commands
```bash
    cd ./docker
    docker compose up --build
```

### to access the project
- for the website: https://localhost
- for phpmyadmin: https://localhost:8081
- for Mailpit: http://localhost:8025
- for database: `db:3306` and dbname is `santaroso`
- database users: `root:root`, `user:password` yes seriously!

