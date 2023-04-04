{crmScope extensionKey='consentactivity'}
    <div class="consentactivity-container">
        {if $error}
            <div>{ts}Sorry, something went wrong. Please try again later!{/ts}</div>
        {else}
            <div>
                <div>
                    {ts 1=$org_name}You have successfully renewed your GDPR consent at %1!{/ts}
                    {if $email_contact}
                        <br>
                        {ts 1=$email_contact}You can revoke your consent or request your personal data deletion at any time by writing to %1 email address.{/ts}
                    {/if}
                </div>
            </div>
        {/if}
    </div>
{/crmScope}
