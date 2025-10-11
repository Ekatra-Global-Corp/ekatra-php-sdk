<?php

namespace Ekatra\Product\Core;

use Ekatra\Product\Exceptions\EkatraValidationException;
use Ekatra\Product\Validators\ProductValidator;
use Ekatra\Product\Transformers\ProductTransformer;
use Ekatra\Product\ResponseBuilder;

/**
 * EkatraProduct - Core product class
 * 
 * This class handles complete product information with variants,
 * specifications, offers, and comprehensive validation.
 */
class EkatraProduct
{
    public ?string $productId = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?string $moreDetails = null;
    public ?string $currency = null;
    public ?string $existingUrl = null;
    public array $keywords = [];
    public array $additionalInfo = [];
    public ?string $specification = null;
    public ?array $specifications = [];
    public ?array $offer = null;
    public array $offers = [];
    public array $variants = [];
    public array $sizes = [];
    public array $supportedCurrency = [];
    public ?string $aspectRatio = null;
    public ?array $metadata = null;
    public ?string $handle = null;
    public array $categories = [];
    public array $tags = [];
    public ?string $countryCode = null;

    private ProductValidator $validator;
    private ProductTransformer $transformer;

    public function __construct()
    {
        $this->validator = new ProductValidator();
        $this->transformer = new ProductTransformer();
    }

    /**
     * Set basic product information
     */
    public function setBasicInfo(string $productId, string $title, string $description, string $currency = 'INR'): self
    {
        $this->productId = $productId;
        $this->title = $title;
        $this->description = $description;
        $this->currency = $currency;
        return $this;
    }

    /**
     * Set product URL
     */
    public function setUrl(string $existingUrl): self
    {
        $this->existingUrl = $existingUrl;
        return $this;
    }

    /**
     * Set keywords
     */
    public function setKeywords($keywords): self
    {
        if (is_string($keywords)) {
            $this->keywords = array_filter(array_map('trim', explode(',', $keywords)));
        } else {
            $this->keywords = $keywords ?? [];
        }
        return $this;
    }

    /**
     * Set additional information
     */
    public function setAdditionalInfo(array $info): self
    {
        $this->additionalInfo = $info;
        return $this;
    }

    /**
     * Set specification (legacy)
     */
    public function setSpecification(?string $spec): self
    {
        $this->specification = $spec;
        return $this;
    }

    /**
     * Set specifications array
     */
    public function setSpecifications(array $specifications): self
    {
        $this->specifications = $specifications;
        return $this;
    }

    /**
     * Add specification
     */
    public function addSpecification(string $key, string $value): self
    {
        $this->specifications[] = ['key' => $key, 'value' => $value];
        return $this;
    }

    /**
     * Set offer (legacy)
     */
    public function setOffer(?string $title, ?string $description): self
    {
        $this->offer = $title ? ['title' => $title, 'description' => $description] : null;
        return $this;
    }

    /**
     * Set offers array
     */
    public function setOffers(array $offers): self
    {
        $this->offers = $offers;
        return $this;
    }

    /**
     * Add offer
     */
    public function addOffer(string $title, array $productOfferDetails): self
    {
        $this->offers[] = [
            'title' => $title,
            'productOfferDetails' => $productOfferDetails
        ];
        return $this;
    }

    /**
     * Add variant
     */
    public function addVariant($variant): self
    {
        if ($variant instanceof EkatraVariant) {
            $this->variants[] = $variant;
        } else {
            // Auto-convert from customer data
            $this->variants[] = EkatraVariant::fromCustomerData($variant);
        }
        return $this;
    }

    /**
     * Add multiple variants
     */
    public function addVariants(array $variants): self
    {
        foreach ($variants as $variant) {
            $this->addVariant($variant);
        }
        return $this;
    }

    /**
     * Set sizes
     */
    public function setSizes(array $sizes): self
    {
        $this->sizes = $sizes;
        return $this;
    }

    /**
     * Add size
     */
    public function addSize(string $id, string $name): self
    {
        $this->sizes[] = ['id' => $id, 'name' => $name];
        return $this;
    }

    /**
     * Set supported currencies
     */
    public function setSupportedCurrency(array $currencies): self
    {
        $this->supportedCurrency = $currencies;
        return $this;
    }

    /**
     * Set handle
     */
    public function setHandle(string $handle): self
    {
        $this->handle = $handle;
        return $this;
    }

    /**
     * Set categories
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * Add category
     */
    public function addCategory(string $category): self
    {
        $this->categories[] = $category;
        return $this;
    }

    /**
     * Set tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add tag
     */
    public function addTag(string $tag): self
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * Set country code
     */
    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * Set aspect ratio
     */
    public function setAspectRatio(string $aspectRatio): self
    {
        $this->aspectRatio = $aspectRatio;
        return $this;
    }

    /**
     * Set metadata
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Set more details
     */
    public function setMoreDetails(string $moreDetails): self
    {
        $this->moreDetails = $moreDetails;
        return $this;
    }

    /**
     * Create product from customer data with smart mapping
     */
    public static function fromCustomerData(array $customerProduct): self
    {
        $product = new self();
        
        // Use transformer for smart mapping
        $mappedData = $product->transformer->mapCustomerData($customerProduct);
        
        // Set basic fields
        if (isset($mappedData['productId'])) {
            $product->productId = $mappedData['productId'];
        }
        
        if (isset($mappedData['title'])) {
            $product->title = $mappedData['title'];
        }
        
        if (isset($mappedData['description'])) {
            $product->description = $mappedData['description'];
        }
        
        if (isset($mappedData['moreDetails'])) {
            $product->moreDetails = $mappedData['moreDetails'];
        }
        
        if (isset($mappedData['currency'])) {
            $product->currency = $mappedData['currency'];
        }
        
        if (isset($mappedData['existingUrl'])) {
            $product->existingUrl = $mappedData['existingUrl'];
        }
        
        if (isset($mappedData['keywords'])) {
            $product->keywords = $mappedData['keywords'];
        }
        
        if (isset($mappedData['additionalInfo'])) {
            $product->additionalInfo = $mappedData['additionalInfo'];
        }
        
        if (isset($mappedData['specification'])) {
            $product->specification = $mappedData['specification'];
        }
        
        if (isset($mappedData['specifications'])) {
            $product->specifications = $mappedData['specifications'];
        }
        
        if (isset($mappedData['offer'])) {
            $product->offer = $mappedData['offer'];
        }
        
        if (isset($mappedData['offers'])) {
            $product->offers = $mappedData['offers'];
        }
        
        if (isset($mappedData['sizes'])) {
            $product->sizes = $mappedData['sizes'];
        }
        
        if (isset($mappedData['supportedCurrency'])) {
            $product->supportedCurrency = $mappedData['supportedCurrency'];
        }
        
        if (isset($mappedData['handle'])) {
            $product->handle = $mappedData['handle'];
        }
        
        if (isset($mappedData['categories'])) {
            $product->categories = $mappedData['categories'];
        }
        
        if (isset($mappedData['tags'])) {
            $product->tags = $mappedData['tags'];
        }
        
        if (isset($mappedData['countryCode'])) {
            $product->countryCode = $mappedData['countryCode'];
        }
        
        if (isset($mappedData['aspectRatio'])) {
            $product->aspectRatio = $mappedData['aspectRatio'];
        }
        
        if (isset($mappedData['metadata'])) {
            $product->metadata = $mappedData['metadata'];
        }
        
        // Handle variants
        if (isset($mappedData['variants']) && is_array($mappedData['variants'])) {
            foreach ($mappedData['variants'] as $variantData) {
                $product->addVariant(EkatraVariant::fromCustomerData($variantData));
            }
        }
        
        return $product;
    }

    /**
     * Validate product data
     * Returns validation result without throwing exceptions
     */
    public function validate(): array
    {
        return $this->validator->validate($this);
    }

    /**
     * Validate product data and throw exception if invalid
     */
    public function validateOrFail(): void
    {
        $validation = $this->validate();
        if (!$validation['valid']) {
            throw new EkatraValidationException(
                'Product validation failed: ' . implode(', ', $validation['errors']),
                $validation['errors']
            );
        }
    }

    /**
     * Transform product to Ekatra format
     * Throws exception if validation fails
     */
    public function toEkatraFormat(): array
    {
        $this->validateOrFail();
        return $this->transformer->toEkatraFormat($this);
    }

    /**
     * Transform product to Ekatra format with validation results
     * Returns both data and validation status
     */
    public function toEkatraFormatWithValidation(): array
    {
        $validation = $this->validate();
        
        if (!$validation['valid']) {
            return ResponseBuilder::validationError($validation, 'Product validation failed');
        }
        
        return ResponseBuilder::success(
            $this->transformer->toEkatraFormat($this),
            ['validation' => $validation],
            'Product details retrieved successfully'
        );
    }

    /**
     * Get product as array
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'title' => $this->title,
            'description' => $this->description,
            'moreDetails' => $this->moreDetails,
            'currency' => $this->currency,
            'existingUrl' => $this->existingUrl,
            'keywords' => $this->keywords,
            'additionalInfo' => $this->additionalInfo,
            'specification' => $this->specification,
            'specifications' => $this->specifications,
            'offer' => $this->offer,
            'offers' => $this->offers,
            'variants' => array_map(function($variant) {
                return $variant instanceof EkatraVariant ? $variant->toArray() : $variant;
            }, $this->variants),
            'sizes' => $this->sizes,
            'supportedCurrency' => $this->supportedCurrency,
            'aspectRatio' => $this->aspectRatio,
            'metadata' => $this->metadata,
            'handle' => $this->handle,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'countryCode' => $this->countryCode
        ];
    }
}
