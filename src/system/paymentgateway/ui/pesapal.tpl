{include file="sections/header.tpl"}

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <img src="system/paymentgateway/ui/pesapal_logo.png" style="height:30px;vertical-align:middle;" alt="Pesapal">
            Pesapal Payment Gateway
        </h3>
    </div>
    <div class="panel-body">
        <form method="post" action="{$_url}paymentgateway/pesapal">
            <div class="form-group">
                <label class="control-label">Consumer Key</label>
                <input type="text" class="form-control" name="pesapal_consumer_key" value="{$_c['pesapal_consumer_key']}" placeholder="Pesapal consumer key from Pesapal merchant dashboard">
            </div>

            <div class="form-group">
                <label class="control-label">Consumer Secret</label>
                <input type="password" class="form-control" name="pesapal_consumer_secret" value="{$_c['pesapal_consumer_secret']}" placeholder="Pesapal consumer secret">
            </div>

            <div class="form-group">
                <label class="control-label">Environment</label>
                <select class="form-control" name="pesapal_environment">
                    <option value="sandbox" {if $_c['pesapal_environment'] eq 'sandbox'}selected{/if}>Sandbox (Testing)</option>
                    <option value="live" {if $_c['pesapal_environment'] eq 'live'}selected{/if}>Live (Production)</option>
                </select>
                <span class="help-block">Use Sandbox for testing. Switch to Live only when you have valid production keys.</span>
            </div>

            <div class="form-group">
                <label class="control-label">Currency</label>
                <select class="form-control" name="pesapal_currency">
                    {foreach $cur as $code => $name}
                        <option value="{$code}" {if $_c['pesapal_currency'] eq $code}selected{/if}>{$name} ({$code})</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label class="control-label">Country Code</label>
                <select class="form-control" name="pesapal_country_code">
                    <option value="UG" {if $_c['pesapal_country_code'] eq 'UG'}selected{/if}>Uganda</option>
                    <option value="TZ" {if $_c['pesapal_country_code'] eq 'TZ'}selected{/if}>Tanzania</option>
                    <option value="KE" {if $_c['pesapal_country_code'] eq 'KE'}selected{/if}>Kenya</option>
                </select>
            </div>

            <div class="form-group">
                <label class="control-label">Business / Brand Name</label>
                <input type="text" class="form-control" name="pesapal_brand_name" value="{$_c['pesapal_brand_name']}" placeholder="e.g. Buku Tu Internet">
                <span class="help-block">Displayed on Pesapal payment page</span>
            </div>

            <div class="form-group">
                <label class="control-label">IPN ID</label>
                <input type="text" class="form-control" name="pesapal_ipn_id" value="{$_c['pesapal_ipn_id']}" placeholder="Auto-registered or paste your IPN ID">
                <span class="help-block">This is auto-registered when you save settings. You can also manually enter it from your Pesapal dashboard.</span>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="glyphicon glyphicon-save"></i> {$_L['Save']}
                </button>
                <a href="{$_url}dashboard" class="btn btn-default">{$_L['Cancel']}</a>
            </div>
        </form>

        <hr>

        <div class="alert alert-info">
            <h4>Setup Instructions</h4>
            <ol>
                <li>Register at <a href="https://www.pesapal.com/" target="_blank">Pesapal.com</a> and create a merchant account</li>
                <li>Get your Consumer Key and Consumer Secret from the Pesapal Merchant Dashboard</li>
                <li>Enter them above and save — the IPN URL will be auto-registered</li>
                <li>Test with Sandbox keys first, then switch to Live when ready</li>
            </ol>
            <p><strong>Supported Countries:</strong> Uganda, Tanzania, Kenya</p>
            <p><strong>Payment Methods:</strong> MTN Mobile Money, Airtel Money, Card (Visa/Mastercard)</p>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
