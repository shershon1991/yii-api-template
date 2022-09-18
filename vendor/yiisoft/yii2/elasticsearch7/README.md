```chenlei 20201126```

```
es7.x版本使用
```

```php
return [
      //....
      'components' => [
          'elasticsearch' => [
              'class' => 'yii\elasticsearch7\Connection',
              'nodes' => [
                  ['http_address' => '127.0.0.1:9200'],
                  // configure more hosts if you have a cluster
              ],
              'dslVersion' => 7, // default is 5
          ],
      ]
  ];
```