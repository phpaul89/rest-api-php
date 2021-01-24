# Overview

The task is to develop a REST API in PHP utilizing a relational database for a banking service. It should be possible to manage customers, giro accounts and transactions.
<br><br>

## Tools

<br>

- Server: Apache 2.2.31, PHP 7.4.1 via MAMP 4.2.0
- Database: MySQL 8.0
- API Testing: Postman
  <br><br>

## Quickstart

<br>

Install and setup the above tools for your PC. Run MAMP (or any other environment tool) to verify that your server is online at e.g. "localhost" and that the database is connected.

Use Postman (postman.com) to send requests to the endpoints listed below. The request data has to be sent "raw" (= JSON) via the request body (see required properties in table) with the property values as String type (see also Troubleshooting).

The response data is echo'd as a JSON and displayed accordingly in the Postman console.

It is possible to create, read and manage customers and giro accounts.
Also, between two giro accounts a wire transfer can be done with 'senderId' being the giro account id of the sending party and 'receiverId' being the giro account id of the receiving party. To authorize the transaction a 'senderPin' is needed which equals the PIN of the giro account of the sending party.

<br><br>

## Endpoints

<br>

|  Method   |          Path          |                    Parameter                    |               Request body               |
| :-------: | :--------------------: | :---------------------------------------------: | :--------------------------------------: |
|  **GET**  |     `api/user/all`     |                        -                        |                    -                     |
|  **GET**  |     `api/user/:id`     |                   Customer ID                   |                    -                     |
| **POST**  |       `api/user`       |                        -                        | firstName, lastName, dateOfBirth, gender |
| **PATCH** |     `api/user/:id`     |                   Customer ID                   |              _any of above_              |
|  **GET**  |     `api/giro/:id`     |                   Account ID                    |                    -                     |
| **POST**  |       `api/giro`       |                        -                        |             name, pin, dispo             |
| **PATCH** |     `api/giro/:id`     |                   Account ID                    |              _any of above_              |
| **PATCH** | `api/giro/:action/:id` | action: 'deposit' or 'withdraw'; id: Account ID |               pin, amount                |
| **POST**  |       `api/wire`       |                        -                        | senderId, receiverId, senderPin, amount  |

<br><br>

## Example request bodies

<br>

POST api/user

```json
{
  "firstName": "John",
  "lastName": "Doe",
  "dateOfBirth": "01.02.1976",
  "gender": "m"
}
```

PATCH api/user/1

```json
{ "firstName": "Jane", "gender": "f" }
```

POST api/giro

```json
{ "name": "Johns Giro", "pin": "1976", "dispo": "0" }
```

PATCH api/giro/1

```json
{ "name": "Janes Giro", "pin": "6791" }
```

PATCH api/giro/deposit/1

```json
{ "pin": "6791", "amount": "500" }
```

PATCH api/giro/withdraw/1

```json
{ "pin": "6791", "amount": "200" }
```

POST api/wire

```json
{ "senderId": "1", "receiverId": "2", "senderPin": "6791", "amount": "150" }
```

<br><br>

## Notes/Troubleshoot

<br>

In case of an error, exception or invalid operation (e.g. transfer money w/o enough funds) the Exception Handler (try/catch) will return a corresponding message hinting the reason.

The most common error will occur if request values are pre-fixed with 0's without using quotation marks, e.g. choosing a PIN: 0231. This PIN will not be recognized by PHP as the 0 equals a false when processing. In this case just put the PIN in quotation marks: "0231". If the values are precautionary put into quotation marks the requests should work again.

This project was done over a weekend and provides many possibilities for improvement and further extensions.

Have fun everyon learning PHP!
