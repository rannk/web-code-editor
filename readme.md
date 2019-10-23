# Web Code Editor
This is a easy code editor rely on web browser. User can use this editor on phone, ipad or desktop, only you have the browser. you can edit your code anywhere.
You could see demo here: http://code.kuyousoft.com:8182/demo_editor/
## How to install

### requirement:
1. web server(like apache or nginx) and PHP environment
 
2. need these php extension libs
    
    * mbstring
    * ssh2
### install
1. git pull these code or download zip file and unzip

2. put these files into your site directory

3. run composer install to download related lib files

4. copy application/config/config_default.php to application/config/config.php

5. set application/config/project directory to writable

6. start your web server then use browser to visit, you would see the editor.

7. click File->New Project to create a new project

we provide these ways to connect your code workspace
    
    local: the code workspace in the web server
  
    sftp: the code workspace in the other remote server, and this server has ssh service.  


    