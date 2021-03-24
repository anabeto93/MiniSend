# MINISEND APP

## Description
This is a transactional emails application built with Laravel and VueJS. It allows you to send emails through the Laravel backend APIs and you can also see how many emails originated from you, and track other interesting data.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.com/anabeto93/MiniSend.svg?branch=master"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
</p>

## Setup
Clone the project and change directory to where it is located.
1. ```composer install```
2. ```cp .env.example .env```
3. ```php artisan key:generate```
4. ```php artisan migrate```
5. ```php artisan storage:link```

If you are windows person or you like simple things, just run ```php artisan serve --port=2021```

I chose `2021` because this year means something special to me 😉

``If you are running on a Mac too, you could do this
```valet link && valet secure``` on the assumption that you have valet installed. You can read about [Valet here](https://laravel.com/docs/8.x/valet)

Now you can visit [http://localhost:2021](http://localhost:2021) and start hacking away at this application.

## Sending Emails

To be able to send emails, you need to modify the `.env` file and specify these properties
```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"
```

You can use your google account as the SMTP driver if you are adventurous or you could sign up on a service like [MailTrap](https://mailtrap.io) to get credentials.

I don't have to explain the UI cos it'll defeat the purpose of a simple design. But regarding the HTML content, you are free to copy and paste any raw html content to be sent as email content.

When you are ready to test real emails? You can try out [MailerSend](https://www.mailersend.com/pricing)

## Searching Emails
You can search by `sender` typing in `from:example@here.com` where `example@here.com` is the senders email.

You can also search for emails sent to a particular recipient by typing `to:him@there.com`, you can space it out as well, `to: him@there.com` doesn't really matter.

You can do same for the subject by `subject: the email subject` or you can choose not to. Anything apart from `from:` and `to:` will be treated as a subject. I know, currently not searching through email content.

NB: You can have various combinations of these keywords, knock yourself out. `from: me@minisend.com to: dream@job.com This is it`

 

## Security Vulnerabilities

If you discover a security vulnerability within this app, please send an e-mail to Richard Opoku via [anabeto93@gmail.com](mailto:anabeto93@gmail.com). All security vulnerabilities will be addressed eventually.
