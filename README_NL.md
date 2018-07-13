![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module voor Drupal Commerce

## Support

Deze betaalmodule ondersteunt Drupal versie **7.x** en maakt gebruik van Commerce versie **1.x**

## Voorbereiding

Voor het gebruik van deze module zijn CardGate RESTful gegevens nodig.  
Bezoek hiervoor [Mijn CardGate](https://my.cardgate.com/) en haal daar je gegevens op,  
of neem hiervoor contact op met je accountmanager.

## Installatie

1. Download en unzip de meest recente [source code](https://github.com/cardgate/drupal-commerce/releases) op je bureaublad.

2. Upload de **commerce_cardgate** map naar de **Drupal modules** map, die je hier kunt vinden:  
   **http://mywebshop.com/htdocs/sites/all/modules/**    
   (Vervang **http://mywebshop.com** met de URL van je webshop.)


## Configuratie

1. Ga naar het **admin** gedeelte van je webshop en selecteer **Modules**.
   Scroll naar het **Commerce (CardGate)** gedeelte.

2. Vink alle **betaalmethoden** aan die je wenst te activeren.
   Scroll naar beneden en klik op **Save configuration**.

3. Ga naar het **admin** gedeelte van je webshop en selecteer **Store, Configuration, Payment methods**.

4. Klik bij **CardGate Generic** op de **Edit** link.
   Klik bij **Actions** op de **Edit** link.
   
5. Vul de **site ID** en **hash key** in, deze kun je vinden bij **Sites** op [Mijn CardGate](https://my.cardgate.com/).

6. Vul de **merchant ID** en **API key** in die je van CardGate hebt ontvangen.

7. Voor het testen van transacties kies **Test mode** en klik op **Save configuration**.

8. Ga terug naar het **admin** gedeelte van je webshop en selecteer **Store, Configuration, Payment methods**.

9. Selecteer in de lijst **Niet ingeschakelde betaalmethoden** de betaalmethode die je wenst te activeren en klik op de **Edit** link.  
    Klik bij **Actions** op de **Edit** link.   
    Selecteer de juiste **Valuta** en **sla het op**.  
    
10. Vink bij **Settings** het **Active** vinkje aan zodat de betaalmethode zichtbaar wordt in het checkout gedeelte van je webshop.

11. Herhaal de **stappen 9 en 10** voor iedere betaalmethode die je wenst te activeren.

12. Zorg ervoor dat je **na het testen** bij de **CardGate Generic module** omschakelt van **Test Mode** naar **Live mode** en sla het op (**Save**).

## Vereisten

Geen verdere vereisten.
