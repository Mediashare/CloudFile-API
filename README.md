# CloudFile
Server file system with simple API.
## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile
cd CloudFile
composer install
php -S localhost:8000 -t public/
```
### Usages
#### Api endpoint
* ``/`` List file(s)
* ``/upload`` Upload file
* ``/show/{id}`` Show file
* ``/download/{id}`` Download file
* ``/remove/{id}`` Remove file
#### Upload file(s)
##### Use curl command line tool
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "file2=@/home/user1/Desktop/image2.jpg" \
  localhost:8000/upload
```
##### Use php script with curl
```php
// send a file
$request = curl_init();
curl_setopt($request, CURLOPT_URL,"http://localhost:8000/upload");
curl_setopt($request, CURLOPT_POST, true);
curl_setopt(
    $request,
    CURLOPT_POSTFIELDS,
    [
        'file' => curl_file_create(realpath('image1.jpg')),
        'file2' => curl_file_create(realpath('image1.jpg'))
    ]
);

// output the response
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($request);

// close the session
curl_close($request);
```