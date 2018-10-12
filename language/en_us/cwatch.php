<?php

$lang['CWatch.name'] = 'CWatch';
$lang['CWatch.module_row.name'] = 'Credential';
$lang['CWatch.module_row_plural.name'] = 'Credentials';

$lang['CWatch.!success.sftp_test'] = 'SFTP connection was successful!';

$lang['CWatch.!tooltip.initiateDns'] = 'Whether to start a scan of the DNS records.';
$lang['CWatch.!tooltip.autoSsl'] = 'Whether to install a Comodo Free SSL Certificate.';

$lang['CWatch.!error.cwatch_email.format'] = 'Invalid email format.';
$lang['CWatch.!error.cwatch_email.unique'] = 'A customer with the given email already exists.';
$lang['CWatch.!error.cwatch_firstname.empty'] = 'Enter a first name.';
$lang['CWatch.!error.cwatch_lastname.empty'] = 'Enter a last name.';
$lang['CWatch.!error.cwatch_country.length'] = 'Invalid county.';
$lang['CWatch.!error.limit_exceeded'] = 'The number of licenses currently on this account exceeds the limit entered.  Please remove licenses before reducing your limit.';
$lang['CWatch.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['CWatch.!error.sftp_test'] = 'The SFTP connection failed! Please check your settings and try again.';

// Manage Module Row Meta
$lang['CWatch.manage.boxtitle'] = 'Manage CWatch Credentials';
$lang['CWatch.manage.title'] = 'API Accounts';
$lang['CWatch.manage.add_module_row'] = 'Add API Credentials';
$lang['CWatch.manage.option_edit'] = 'Edit';
$lang['CWatch.manage.delete'] = 'Delete';
$lang['CWatch.manage.confirm_delete'] = 'Are you sure you want to delete this user? ';
$lang['CWatch.manage.no_results'] = 'No accounts found';


$lang['CWatch.manage.heading_username'] = 'Username';
$lang['CWatch.manage.heading_options'] = 'Options';

$lang['Cwatch.service_field.email'] = 'Email';
$lang['Cwatch.service_field.firstname'] = 'First Name';
$lang['Cwatch.service_field.lastname'] = 'Last Name';
$lang['Cwatch.service_field.country'] = 'Country';

// Add Module Row
$lang['CWatch.add_row.box_title'] = 'Add API Account';
$lang['CWatch.add_row.basic_title'] = 'User Settings';
$lang['CWatch.add_row.field_username'] = 'Username';
$lang['CWatch.add_row.field_password'] = 'Password';
$lang['CWatch.add_row.field_sandbox'] = 'Enable SandBox';
$lang['CWatch.add_row.add_btn'] = 'Add Account';

// Edit Module Row
$lang['CWatch.edit_row.box_title'] = 'Edit Credentials';
$lang['CWatch.edit_row.basic_title'] = 'User Settings';
$lang['CWatch.edit_row.field_username'] = 'Username';
$lang['CWatch.edit_row.field_password'] = 'Password';
$lang['CWatch.edit_row.field_sandbox'] = 'Enable SandBox';
$lang['CWatch.edit_row.add_btn'] = 'Edit Credentials';

// Service info
$lang['CWatch.service_info.option_login'] = 'Log in to cWatch';

// Tab licenses
$lang['CWatch.tab_licenses.licenses'] = 'Manage Licenses';
$lang['CWatch.tab_licenses.add'] = 'Add New Site';
$lang['CWatch.tab_licenses.licenseKey'] = 'License Key';
$lang['CWatch.tab_licenses.type'] = 'License Type';
$lang['CWatch.tab_licenses.domain'] = 'Domain';
$lang['CWatch.tab_licenses.status'] = 'Domain Status';
$lang['CWatch.tab_licenses.malware_scanner'] = 'Malware Scanner';
$lang['CWatch.tab_licenses.actions'] = 'Actions';
$lang['CWatch.tab_licenses.no_results'] = 'No licenses have been provisioned for this customer.';

$lang['CWatch.tab_licenses.not_applicable'] = 'N/A';
$lang['CWatch.tab_licenses.remove_site'] = 'Remove Site';
$lang['CWatch.tab_licenses.add_site'] = 'Add Site';
$lang['CWatch.tab_licenses.confirm_delete'] = 'Are you sure you want to remove this domain?';

$lang['CWatch.tab_licenses.domain'] = 'Domain Name';
$lang['CWatch.tab_licenses.initiateDns'] = 'Initial DNS';
$lang['CWatch.tab_licenses.autoSsl'] = 'AutoSSL';
$lang['CWatch.tab_licenses.submit'] = 'Submit';

// Tab malware
$lang['CWatch.tab_malware.malware'] = 'Malware Control';
$lang['CWatch.tab_malware.add_scanner'] = 'Add Scanner';
$lang['CWatch.tab_malware.test_credentials'] = 'Test These Credentials';
$lang['CWatch.tab_malware.domainname'] = 'Domain Name';
$lang['CWatch.tab_malware.ftppassword'] = 'FTP Password';
$lang['CWatch.tab_malware.ftpusername'] = 'FTP Username';
$lang['CWatch.tab_malware.host'] = 'FTP HostName';
$lang['CWatch.tab_malware.port'] = 'FTP Port';
$lang['CWatch.tab_malware.path'] = 'Site Directory';

$lang['CWatch.tab_malware.submit'] = 'Submit';

// Get site statuses
$lang['CWatch.getsitestatuses.waiting'] = 'Waiting';
$lang['CWatch.getsitestatuses.site_inprogress'] = 'Site Provision in Progress';
$lang['CWatch.getsitestatuses.site_retry'] = 'Retrying Site Provision';
$lang['CWatch.getsitestatuses.site_completed'] = 'Site Added';
$lang['CWatch.getsitestatuses.site_failed'] = 'Site Provision Failed';
$lang['CWatch.getsitestatuses.dns_inprogress'] = 'DNS Scan in Progress';
$lang['CWatch.getsitestatuses.dns_retry'] = 'Retrying DNS Scan';
$lang['CWatch.getsitestatuses.dns_completed'] = 'DNS Scan Completed';
$lang['CWatch.getsitestatuses.dns_failed'] = 'Site Added, DNS Scan Failed';
$lang['CWatch.getsitestatuses.ssl_inprogress'] = 'SSL Install in Progress';
$lang['CWatch.getsitestatuses.ssl_retry'] = 'Retrying SSL Install';
$lang['CWatch.getsitestatuses.ssl_completed'] = 'SSL Certificate Added';
$lang['CWatch.getsitestatuses.ssl_fail'] = 'Site Added, SSL Install Failed';
