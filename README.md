# Praca Magisterska
Praca Magisterska (mgr-inż) 2018-2020 - Sebastian S.

Aby uruchomić projekt należy:
1. Posiadać Dockera (lub umieć samemu postawić środowisko - wszystko jest opisane w `/docker/docker-compose.yml`)
2. Przejść do folderu `docker` i w nim wykonać:<br />`docker-compose up -d`
3. Po zbudowaniu kontenerów wejść do kontenera php74<br />(dla Windowsa jest to komenda: `winpty docker exec -it php74 bash`)
4. W folderze (w kontenerze) `/data/work/magister` odpalić:<br /> `composer install`
5. Skonfigurować enva<br />klucze `DATABASE_3NF_URL` i `DATABASE_NON_3NF_URL` muszą wskazywać na tę samą bazę danych<br />np. `DATABASE_NON_3NF_URL=pdo-pgsql://root:root@192.168.100.3:5432/db_3nf?serverVersion=12&charset=utf8`
6. Środowisko jest gotowe. Teraz można stworzyć bazy 3NF i non3NF. Po stworzeniu baz można wykonywać testy wydajnościowe.<br />Folder: `work/magister/src/Command`
