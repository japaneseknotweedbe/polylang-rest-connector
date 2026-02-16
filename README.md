# polylang-rest-connector
Expose Polylang language and translation data to the WordPress REST API to allow an external automation tool (i.e. n8n) to read translation status and link new translations programmatically.

### Instructions

1.  Create a file named polylang-rest-connector.php.
    
2.  Paste the code below into that file.
    
3.  Upload it to your wp-content/plugins/ folder and activate it, OR place it inside wp-content/mu-plugins/ to force activation.
    

### Implementation Details

*   **Security:** Checks if Polylang is active (function\_exists('pll\_default\_language')) before executing to prevent site crashes.
    
*   **Dynamic Support:** Automatically detects all public post types (Posts, Pages, etc.) via get\_post\_types.
    
*   **Read (GET):** Adds lang (string) and translations (object) to the API response.
    
*   **Write (POST/PUT):** Hooks into rest\_insert\_{post\_type} to handle the side-effects of setting language and linking translations.
