# BucoOrderDocumentsApi
Shopware plugin which adds a REST API endpoint to access and delete order documents.

## Features
This plugin adds a new REST API endpoint to access and delete the order documents including
their attributes. Creating and modifying order documents it currently not implemented. The access to the endpoint can be restricted with the access
control list (ACL) resource <code>BucoOrderDocuments</code> and the privileges <code>read</code> and
<code>delete</code> in the user manager.

<p>The REST API can be accessed in the following ways:</p>


- <code>GET /api/BucoOrderDocuments/</code> returns a <b>listing</b>. The <a href="https://developers.shopware.com/developers-guide/rest-api/#filter,-sort,-limit,-offset">well known</a> limit, order and query parameters can be applied.
  
  Example output: 
    ```javascript
    {
        "data": [
            {
                "id": 11756,
                "date": "2017-07-31T00:00:00+0200",
                "typeId": 1,
                "customerId": 6,
                "orderId": 1,
                "amount": 1598,
                "documentId": "55483",
                "hash": "7d2431092sadsa3a8047756edf0fec2da",
                "attribute": { // null, if no attributes available
                    "id": 11668,
                    "documentId": 11756,
                    "someExampleAttribute": 42,
                }
            },
            { ... }
        ],
        "total": 1337,
        "success": true
    }
    ```
- <code>GET /api/BucoOrderDocuments/{id}</code> returns a <b>specific</b> order document with a base64 encoded representation of the PDF document directly within the JSON response. To retrieve the PDF document directly without the meta data, include or set the MIME type <code>application/pdf</code> in the request header <code>Accept</code>.
  
  Example output (for JSON representation):
    ```javascript
    {
        "data": {
            "id": 11756,
            "date": "2017-07-31T00:00:00+0200",
            "typeId": 1,
            "customerId": 6,
            "orderId": 1,
            "amount": 1598,
            "documentId": "55483",
            "hash": "7d2431092sadsa3a8047756edf0fec2da",
            "attribute": { // null, if no attributes available
                "id": 11668,
                "documentId": 11756,
                "someExampleAttribute": 42,
            },
            "pdfDocument": "some random base64 encoded data[...]" // null, if file do not exist
        },
        "success": true
    }
    ```
- <code>DELETE /api/BucoOrderDocuments/{id}</code> deletes a <b>specific</b> order document.
  Example output:
    ```javascript
    {
        "success": true
    }
    ```

## Feature Ideas
- Implement POST and PUT methods
  - ```POST``` Generate document via Shopware
  - ```POST``` Upload externally generated document
  - ```PUT``` manipulate meta data like amount and attributes
  - ```PUT``` upload PDF file

## Compatibility
* Shopware >= 5.2.0
* PHP >= 7.0

## Installation

### Git Version
* Checkout plugin in `/custom/plugins/BucoOrderDocumentsApi`
* Install and active plugin with the Plugin Manager

### Install with composer
* Change to your root installation of Shopware
* Run command `composer require buddha-code/buco-order-documents-api`
* Install and active plugin with `./bin/console sw:plugin:install --activate BucoOrderDocumentsApi`

## Contributing
Feel free to fork and send pull requests!

## Licence
This project uses the [GPLv3 License](LICENCE).
