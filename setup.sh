docker-compose up -d
alias eewp='docker run  --network eecommand_main --link database:database --env-file ./DockerData/db.env --name eewp --rm -it eewp wp ee'