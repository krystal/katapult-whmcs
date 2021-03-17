{* These templates need to be BS3 and BS4 compatible *}
<div class="text-left">

    <script>

        let kvmService = {$katapultVmService|json_encode};

    </script>

    <div class="row">

        <div class="col-md-12">

            <hr>

            <form>

                <div class="form-group">

                    <label for="kvm_username">Username</label>
                    <input type="text" class="form-control" id="kvm_username" placeholder="Username" value="{$username|htmlentities}" readonly>

                </div>

                <div class="form-group">

                    <label for="kvm_password">Password</label>
                    <input type="password" class="form-control" id="kvm_password" placeholder="Password" value="{$password|htmlentities}" readonly>
                    <button class="btn btn-primary btn-sm" type="button" id="kvm_password_toggle" style="margin-top: 0.5rem"></button>

                </div>

            </form>

        </div>

    </div>

</div>

