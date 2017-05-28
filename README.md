# amazon-json-api

ASINで商品情報を引っ張ってきて、JSONで返すAPI。

# Usage

```
$ git clone https://github.com/tmsanrinsha/amazon-json-api.git
$ cd amazon-json-api
$ composer install
```

`src/settings_secret.php`に設定を書く。

```php
<?php
return [
    'AWSAccessKeyId' => '****',
    'AWSSecretKey' => '****',
    'AWSAssociateTag' => '****',
];
```

```
$ php -S 0.0.0.0:8080 -t public public/index.php
```

```
$ curl 'http://0.0.0.0:8080/asin/B01MU9MB8Y'
```
