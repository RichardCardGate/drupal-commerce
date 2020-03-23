![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module for Drupal Commerce

[![Build Status](https://travis-ci.org/cardgate/drupal-commerce.svg?branch=master)](https://travis-ci.org/cardgate/drupal-commerce)

## Support

This payment module works with Drupal version **7.x** and makes use of Commerce version **1.x**

## Preparation

The usage of this module requires that you have obtained CardGate RESTful API credentials.
Please visit [My CardGate](https://my.cardgate.com/) and retrieve your credentials, or contact your accountmanager.

## Installation

1. Download and unzip the most recent [cardgate.zip](https://github.com/cardgate/drupal-commerce/releases) file on your desktop.

2. Upload the **sitess** folder of the zip file to the  **root** folder of your webshop.

## Configuration

1. Go to the **admin** section of your webshop and select **Modules**.
   Scroll to the **Commerce (CardGate)** section.

2. Checkmark all the **payment methods** that you wish to activate.
   Scroll down and click **Save configuration**.

3. Go to the **admin** section of your webshop and select **Store, Configuration, Payment methods**.

4. Click at **CardGate Generic** on the **Edit** link.
   Click at **Actions** on the **Edit** link.

5. Enter the **site ID** and **hash key**, which you can find at **Sites** on [My CardGate](https://my.cardgate.com/).

6. Enter the **merchant ID** and the **API key** which you have received from CardGate.

7. To test transactions, select **Test mode** and click **Save configuration**.

8. Go back to the **admin** section of your webshop and select **Store, Configuration, Payment methods**.

9. In the list **not enabled payment methods** select the payment method you wish to activate and click on the **Edit** link.
    At **Actions**, click on the **Edit** link.
    Select the appropriate **Currency** and **save it**.

10. At **Settings**, checkmark **Active** so the payment method will be visible in the checkout section of your webshop.

11. Repeat **steps 9 and 10** for each payment method you wish to activate.

12. When you are **finished testing** make sure that you switch the **CardGate Generic module** from **Test Mode** to **Live mode** and save it (**Save**).

## Requirements

No further requirements.
