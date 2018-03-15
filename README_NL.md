![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module voor Drupal Commerce

[![Total Downloads](https://img.shields.io/packagist/dt/cardgate/drupal_commerce.svg)](https://packagist.org/packages/cardgate/drupal_commerce)
[![Latest Version](https://img.shields.io/packagist/v/cardgate/drupal_commerce.svg)](https://github.com/cardgate/drupal_commerce/releases)
[![Build Status](https://travis-ci.org/cardgate/drupal_commerce.svg?branch=master)](https://travis-ci.org/cardgate/drupal_commerce)

## Support

Deze betaalmodule ondersteunt Drupal versie **7.x** en maakt gebruik van Commerce versie **1.x**

## Voorbereiding

Voor het gebruik van deze module zijn CardGate RESTful gegevens nodig.
Bezoek hiervoor [Mijn CardGate](https://my.cardgate.com/) en haal daar je 
RESTful API gebruikersnaam en wachtwoord op, of neem contact op met je accountmanager.

## Installatie

1. Download en unzip het **commerce_cardgate.zip** bestand op je bureaublad.

2. Installeer de plug-in via **Admin, Modules, Install new module** van je webshop.


## Configuratie

1. Ga naar het **admin** gedeelte van je webshop en selecteer **Modules**.
   Scroll naar het **Commerce (CardGate)** gedeelte.

2. Vink alle **betaalmethoden** aan die je wenst te activeren.
   Scroll naar beneden en klik op **Save configuration**.

3. Ga naar het **admin** gedeelte van je webshop en selecteer **Store, Configuration, Payment methods**.

4. Klik bij **CardGate Generic** op de **Edit** link.
   Klik bij **Actions** op de **Edit** link.
   
5. Vul de **Merchant ID** en **Merchant API key** in die je van CardGate hebt ontvangen.

6. Vul de **Site ID** in, deze kun je vinden bij **Sites** op [Mijn CardGate](https://my.cardgate.com/).

7. Vul de **Gateway URL** in, de standaard waarde is **secure.curopayments.net**

8. Vul de **Test Gateway URL** in, de standaard waarde is **secure-staging.curopayments.net**

9. Voor het testen van transacties kies **Test mode** en klik op **Save configuration**.

10. Ga terug naar het **admin** gedeelte van je webshop en selecteer **Store, Configuration, Payment methods**.

11. Selecteer in de lijst **Niet ingeschakelde betaalmethoden** de betaalmethode die je wenst te activeren en klik op de **Edit** link.
    Klik bij **Actions** op de **Edit** link.
    Selecteer de juiste **Valuta** en **sla het op**.
    
12. Vink bij **Settings** het **Active** vinkje aan zodat de betaalmethode zichtbaar wordt in het checkout gedeelte van je webshop.

13. Herhaal de **stappen 11 tot en met 12** voor iedere betaalmethode die je wenst te activeren.

14. Zorg ervoor dat je **na het testen** bij de **CardGate Generic module** omschakelt van **Test Mode** naar **Live mode** en sla het op (**Save**).

## Vereisten

Geen verdere vereisten.
