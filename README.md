Hello claude, I need to add some features to products in woocommerce via a plugin. The general gist is to create products that can be auctioned. We will need things like expiration dates and actions that will update maximum bids while checking reserves but I don't want you to write code right away. I'd like to tell you the steps i'm imagining and then you tell me if logically this would be the best way to guide both of us through you writing all the code for the plugin while I test it along the way.

Step 1, create a new product type that can be selected from the Product Data>Product Type drop down. Have it inherit all the options from a standard product but also add a new tab called auction details to the product meta box that will contain the data for: Start time. end time, starting price, reserve price.

Step 2, create all of the logic for storing bidding information and handling bidding actions as well as logic for winning bidders items getting added to the woocommerce checkout cart automatically by logged in users of woocommerce.

Step 3. create all of the front end enhancements to the product page to show bid history number of bids and all information and actions to make a fully function item bidding auction like ebay.

