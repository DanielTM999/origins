<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute(Attribute::TARGET_METHOD)]
    class ContentType{
        public const JSON = "application/json";
        public const HTML = "text/html";
        public const XML  = "application/xml";

        public function __construct(public string $location = self::HTML) {}
    }

?>
