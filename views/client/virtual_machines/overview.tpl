{* These templates need to be BS3 and BS4 compatible *}
<div class="text-left">

    <script>

        let kvmService = {$katapultVmService|json_encode};

    </script>

    <hr>

    <div class="row">

        <div class="col-md-6 col-sm-12">

            <h5>Server state</h5>
            <span class="kvm-state state--{$katapultVmService['vm']['state']|htmlentities} for-template--{$template|htmlentities}">{$katapultVmService['vm']['state']|replace:'_':' '|htmlentities}</span>

            {if $dedicatedip}

                <h5 style="margin-top: 2rem">SSH access</h5>

                <code>ssh -l {$username|htmlentities} {$dedicatedip|htmlentities}</code>

                <br>

                <small>SSH is available on most servers, and is the preferred way to login.</small>

            {/if}

            <h5 style="margin-top: 2rem">Console access</h5>

            <form method="get" target="kvm_console">

                <input type="hidden" name="action" value="productdetails">
                <input type="hidden" name="id" value="{$id|htmlentities}">
                <input type="hidden" name="dosinglesignon" value="1">
                <input type="hidden" name="knrp" value="{WHMCS\Module\Server\Katapult\Helpers\Replay::getToken()|htmlentities}">

                <button class="btn btn-primary">Launch console</button>

            </form>

            <small>The console can be used to access the server when the network is unavailable.</small>

        </div>

        <div class="col-md-6 col-sm-12">

            <h5>Login details</h5>

            <div class="form-group">

                <label for="kvm_username">Username</label>
                <input type="text" class="form-control" id="kvm_username" placeholder="Username" value="{$username|htmlentities}" readonly>

            </div>

            <div class="form-group">

                <label for="kvm_password">Password</label>
                <input type="password" class="form-control" id="kvm_password" placeholder="Password" value="{$password|htmlentities}" readonly>
                <button class="btn btn-secondary btn-sm" type="button" id="kvm_password_toggle" style="margin-top: 0.5rem"></button>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-12">

            <iframe title="Console" name="kvm_console"></iframe>

        </div>

    </div>

</div>

