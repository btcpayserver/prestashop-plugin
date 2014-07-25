{capture name=path}{l s='Bictoin payment.' mod='bitpay'}{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='bitpay'}</h2>

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='bitpay'}</p>
{else}

<h3>{l s='Bitcoin payment.' mod='bitpay'}</h3>

<form action="{$link->getModuleLink('bitpay', 'validation', [], true)|escape:'html'}" method="post">
<p>
	<img src="{$this_path}bitcoin.png" alt="{l s='Pay with BitPay' mod='bitpay'}" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen to pay by Bitcoin.' mod='bitpay'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='bitpay'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='bitpay'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='bitpay'}
    {/if}
</p>
<p>
	{l s='Your Bitcoin invoice will be displayed on the next page.' mod='bitpay'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order."' mod='bitpay'}.</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='bitpay'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Bitcoin Payments' mod='bitpay'}</a>
</p>
</form>
{/if}

