# Lookup API

###Refresh database
```
php artisan physicians:refresh
```

###Order of seeding
1. Locations
2. Specialties
3. SpecialtySubspecialty
4. Aliases
5. SpecialtyAlias
6. Physicians

```
php artisan db:seed --class=LocationTableSeeder   
php artisan db:seed --class=SpecialtyTableSeeder  
php artisan db:seed --class=SpecialtySubspecialtyTableSeeder  
php artisan db:seed --class=AliasTableSeeder  
php artisan db:seed --class=CreateSpecialtyAliasTableSeeder  
php artisan db:seed --class=PhysicianTableSeeder  
```

###When things don't work as expected
```
php artisan cache:clear 
composer dump-autoload
```

###When we have Github permission problems on the server:
```
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa 
```

###Example of importing into physicians table after exporting from prod
```
LOAD DATA LOCAL INFILE
  '/home/vagrant/Sites/lookup-api/database/seeds/data/physicians-2018-11-07-for-import.csv'
  INTO TABLE physicians
  FIELDS 
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
  LINES 
    TERMINATED BY '\n'
```
