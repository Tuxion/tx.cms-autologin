tx.cms-autologin
================

A component for the Tuxion CMS that creates autologin links.
* Passwords are not revealed through generated links.
* Expiration dates can be set for each link.
* When a link is expired, the regular login screen will be displayed.
* Login links will not be generated nor usable for administrator accounts, to prevent compromised administrator accounts.

Note: this component depends on the Tuxion CMS and the `account` component.

Warning and disclaimer
----------------------

The only reason this component exists is that in some situations it provides more user-friendliness, however we suggest you refrain from using it.
Because a login link generated with this component gives full access to an account it's a security risk that you should take seriously.
A generated link is much less safe than using the built in e-mail/username and password login system of the Tuxion CMS.
If your website deals with any sensitive data at all that your users have access to we highly recommend you do not install this component.
If you are unsure about the risks or whether your website deals with sensitive information, please do not install this component.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. For full details see the `LICENCE` file.
