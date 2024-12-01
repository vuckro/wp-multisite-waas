<?php

declare (strict_types=1);
namespace WP_Ultimo\Dependencies;

require_once './vendor/autoload.php';
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Hostname;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecord;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordType;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordCollection;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Factories\SpatieDNS;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Mappers\Dig;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Subscribers\STDIOSubscriber;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Cached;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Chain;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\CloudFlare;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\GoogleDNS;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\LocalSystem;
use WP_Ultimo\Dependencies\Symfony\Component\Cache\Adapter\FilesystemAdapter;
\class_alias(Hostname::class, 'WP_Ultimo\\Dependencies\\Hostname');
\class_alias(DNSRecord::class, 'WP_Ultimo\\Dependencies\\DNSRecord');
\class_alias(DNSRecordType::class, 'WP_Ultimo\\Dependencies\\DNSRecordType');
\class_alias(DNSRecordCollection::class, 'WP_Ultimo\\Dependencies\\DNSRecordCollection');
$stdOut = new \SplFileObject('php://stdout');
$stdErr = new \SplFileObject('php://stderr');
$IOSubscriber = new STDIOSubscriber($stdOut, $stdErr);
$localSystemResolver = new LocalSystem();
$localSystemResolver->addSubscriber($IOSubscriber);
$googleDNSResolver = new GoogleDNS();
$googleDNSResolver->addSubscriber($IOSubscriber);
$cloudFlareResolver = new CloudFlare();
$cloudFlareResolver->addSubscriber($IOSubscriber);
$digResolver = new \WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Dig(new SpatieDNS(), new Dig());
$digResolver->addSubscriber($IOSubscriber);
$chainResolver = new Chain($cloudFlareResolver, $googleDNSResolver, $localSystemResolver);
$cachedResolver = new Cached(new FilesystemAdapter(), $chainResolver);
$cachedResolver->addSubscriber($IOSubscriber);
