# symfony-rest-test

## install

`git clone`

`composer install`

create database

edit .env

`DATABASE_URL=mysql://username:password@host:3306/database?serverVersion=5.7&charset=utf8`

`bin/console doctrine:migrations:migrate`

## usage in PHPStorm http scratch

```
GET http://symfony.local/

###

GET http://symfony.local/generate-products

###

GET http://symfony.local/products

###

POST http://symfony.local/order/create
Content-Type: application/json

[
    {"id":  1, "qty":  1},
    {"id":  2, "qty":  2}
]

###

POST http://symfony.local/order/pay
Content-Type: application/json

{
  "orderId": 1,
  "sum": 1744.56
}

###
```
