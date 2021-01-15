{*
 * Global Pay
 *
 * HTML to be displayed in the order confirmation page
 *
 * @author Erick Estrada<sneider86@gmail.com>
 * @license https://opensource.org/licenses/afl-3.0.php
 *}
<!doctype html>
    <html lang="{$language.iso_code}">
    <head>
      {block name='head'}
        {include file='_partials/head.tpl'}
      {/block}
    </head>

    <body id="{$page.page_name}" class="{$page.body_classes|classnames}">
        {hook h='displayAfterBodyOpeningTag'}
        <main>
            <header id="header">
                {block name='header'}
                {include file='_partials/header.tpl'}
                {/block}
            </header>
            <h1>Compra Fallida!</h1>
            <P>Ha fallado la compra.</P>
            <footer id="footer">
            {block name="footer"}
                {include file="_partials/footer.tpl"}
            {/block}
            </footer>
        </main>
      {hook h='displayBeforeBodyClosingTag'}
      {block name='javascript_bottom'}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
      {/block}
    </body>
</html>
