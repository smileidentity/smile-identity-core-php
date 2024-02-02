# Smile Identity PHP Server Side SDK

Smile Identity provides the best solutions for real time Digital KYC, identity verification, user onboarding, and user authentication across Africa. Our server side libraries make it easy to integrate us on the server-side. Since the library is server-side, you will be required to pass the images (if required) to the library.

If you haven’t already, [sign up for a free Smile Identity account](https://usesmileid.com/talk-to-an-expert), which comes with Sandbox access.

Please see [CHANGELOG.md](CHANGELOG.md) for release versions and changes.

## Features

The library exposes four classes namely; the WebApi class, the IDApi class, the Signature class, and the Utilities class.

The WebApi class has the following public methods:

- `submit_job` - handles submission of any of Smile Identity products that requires an image i.e. [Biometric KYC](https://docs.usesmileid.com/products/biometric-kyc), [Document Verification](https://docs.usesmileid.com/products/document-verification), [SmartSelfieTM Authentication](https://docs.usesmileid.com/products/biometric-authentication) and [Business Verification](https://docs.usesmileid.com/products/for-businesses-kyb/business-verification).
- `get_web_token` - handles generation of web token, if you are using the [Hosted Web Integration](https://docs.usesmileid.com/web-mobile-web/web-integration-beta).

The IDApi class has the following public method:

- `submit_job` - handles submission of [Enhanced KYC](https://docs.usesmileid.com/products/identity-lookup) and [Basic KYC](https://docs.usesmileid.com/products/id-verification).

The Signature class has the following public methods:

- `generate_signature` - generate a signature which is then passed as a signature param when making requests to the Smile Identity server
- `confirm_signature` - ensure a response is truly from the Smile Identity server by confirming the incoming signature

The Utilities Class allows you as the Partner to have access to our general Utility functions to gain access to your data. It has the following public methods:

- `get_job_status` - retrieve information & results of a job. Read more on job status in the [Smile Identity documentation](https://docs.usesmileid.com/further-reading/job-status).
- `get_smile_id_services` - general information about different smile identity products such as required inputs for each supported id type.

## Dependencies

* Composer build tool
- php: >= 7.4
- ext-curl: *
- ext-json: *
- ext-openssl: *
- ext-zip: *
- guzzlehttp/guzzle: ^7.0
- letsdrink/ouzo-goodies: ~1.0

## Documentation

For extensive instructions on usage of the library and sample codes, please refer to the [official Smile Identity documentation](https://docs.usesmileid.com/server-to-server/php).

Before that, you should take a look at the examples in the [examples](/examples) folder.

## Installation

### Installing from the Repository

Download [smile-identity-core-php repository](https://github.com/smileidentity/smile-identity-core-php) to a directory on your server where PHP and Composer is installed.

In that directory, run `composer install`

### Installing from Packagist

View the package on [Packagist](https://packagist.org/packages/smile-identity/smile-identity-core).

Alternatively, the package can be searched locally from a composer-based project by typing the command `composer search <PACKAGE_NAME>` in the command line where `PACKAGE_NAME` can the full name of the package (in this case `smile-identity/smile-identity-core`) or any part of the name distinct enough to return a match.

In the project's directory, run:

```shell
composer require smile-identity/smile-identity-core
```

## Getting Help

For usage questions, the best resource is [our official documentation](https://docs.usesmileid.com/). However, if you require further assistance, you can file a [support ticket via our portal](https://portal.usesmileid.com/partner/support/tickets) or visit the [contact us page](https://usesmileid.com/company/contact-us) on our website.

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/smileidentity/smile-identity-core-php

## License

MIT License
