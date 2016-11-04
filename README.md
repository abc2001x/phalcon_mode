1.mv config.php.exsample config.php

2.nginx 设置 public为root
##.dddddddd

3.nginx 配置
server {

    listen   80;
    server_name localhost;

    index index.php;
    set $root_path '/www/phalcon_mode/public';
    root $root_path;

    try_files $uri $uri/ @rewrite;

    location @rewrite {
        rewrite ^/(.*)$ /index.php?_url=/$1;
    }

    location ~ \.php {
        #fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_pass 127.0.0.1:9000;

        fastcgi_index index.php;

        include /etc/nginx/fastcgi_params;

        fastcgi_split_path_info       ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO       $fastcgi_path_info;
        fastcgi_param APPLICATION_ENV "development";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* ^/(css|img|js|flv|swf|download)/(.+)$ {
        root $root_path;
    }

    location ~ /\.ht {
        deny all;
    }

}