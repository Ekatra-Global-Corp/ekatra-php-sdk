<?php

namespace Ekatra\Product\Core;

use Ekatra\Product\Exceptions\EkatraValidationException;
use Ekatra\Product\Validators\VariantValidator;
use Ekatra\Product\Transformers\VariantTransformer;

/**
 * EkatraVariant - Core variant class for product variants
 * 
 * This class handles individual product variants with comprehensive
 * validation, transformation, and mapping capabilities.
 */
class EkatraVariant
{
    public ?string $name = null;
    public ?string $color = null;
    public ?string $size = null;
    public int $quantity = 0;
    public float $mrp = 0;
    public float $sellingPrice = 0;
    public ?float $discountPercent = null;
    public array $videoUrls = [];
    public array $images = [];
    public ?string $id = null;
    public ?float $weight = null;
    public ?string $thumbnail = null;
    public array $mediaList = [];
    public array $variations = [];

    private VariantValidator $validator;
    private VariantTransformer $transformer;

    public function __construct()
    {
        $this->validator = new VariantValidator();
        $this->transformer = new VariantTransformer();
    }

    /**
     * Set basic variant information
     */
    public function setBasicInfo(string $name, int $quantity, float $mrp, float $sellingPrice): self
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->mrp = $mrp;
        $this->sellingPrice = $sellingPrice;
        
        // Auto-calculate discount if not set
        if ($this->discountPercent === null && $mrp > $sellingPrice) {
            $this->discountPercent = round((($mrp - $sellingPrice) / $mrp) * 100, 2);
        }
        
        return $this;
    }

    /**
     * Set variant attributes
     */
    public function setAttributes(?string $color = null, ?string $size = null): self
    {
        $this->color = $color;
        $this->size = $size;
        return $this;
    }

    /**
     * Set media information
     */
    public function setMedia(array $images = [], array $videos = []): self
    {
        $this->images = is_array($images) ? $images : [$images];
        $this->videoUrls = is_array($videos) ? $videos : [$videos];
        
        // Remove empty values
        $this->images = array_filter($this->images);
        $this->videoUrls = array_filter($this->videoUrls);
        
        return $this;
    }

    /**
     * Set discount percentage
     */
    public function setDiscount(float $discountPercent): self
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    /**
     * Set variant ID
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set weight
     */
    public function setWeight(float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Set thumbnail URL
     */
    public function setThumbnail(string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    /**
     * Add media item
     */
    public function addMediaItem(array $mediaItem): self
    {
        $this->mediaList[] = $mediaItem;
        return $this;
    }

    /**
     * Add variation
     */
    public function addVariation(array $variation): self
    {
        $this->variations[] = $variation;
        return $this;
    }

    /**
     * Add multiple variations
     */
    public function addVariations(array $variations): self
    {
        foreach ($variations as $variation) {
            $this->addVariation($variation);
        }
        return $this;
    }

    /**
     * Set variations array
     */
    public function setVariations(array $variations): self
    {
        $this->variations = $variations;
        return $this;
    }

    /**
     * Add media to variant
     */
    public function addMedia(array $media): self
    {
        $this->mediaList[] = $media;
        return $this;
    }

    /**
     * Add multiple media items
     */
    public function addMediaList(array $mediaList): self
    {
        foreach ($mediaList as $media) {
            $this->addMedia($media);
        }
        return $this;
    }

    /**
     * Set media list
     */
    public function setMediaList(array $mediaList): self
    {
        $this->mediaList = $mediaList;
        return $this;
    }

    /**
     * Set color
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Set size
     */
    public function setSize(string $size): self
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Create variant from customer data with smart mapping
     */
    public static function fromCustomerData(array $customerVariant): self
    {
        $variant = new self();
        
        // Use transformer for smart mapping
        $mappedData = $variant->transformer->mapCustomerData($customerVariant);
        
        // Set basic info
        if (isset($mappedData['name'])) {
            $variant->name = $mappedData['name'];
        }
        
        if (isset($mappedData['color'])) {
            $variant->color = $mappedData['color'];
        }
        
        if (isset($mappedData['size'])) {
            $variant->size = $mappedData['size'];
        }
        
        if (isset($mappedData['quantity'])) {
            $variant->quantity = (int) $mappedData['quantity'];
        }
        
        if (isset($mappedData['mrp'])) {
            $variant->mrp = (float) $mappedData['mrp'];
        }
        
        if (isset($mappedData['sellingPrice'])) {
            $variant->sellingPrice = (float) $mappedData['sellingPrice'];
        }
        
        if (isset($mappedData['discountPercent'])) {
            $variant->discountPercent = (float) $mappedData['discountPercent'];
        }
        
        if (isset($mappedData['images'])) {
            $variant->images = $mappedData['images'];
        }
        
        if (isset($mappedData['videoUrls'])) {
            $variant->videoUrls = $mappedData['videoUrls'];
        }
        
        if (isset($mappedData['id'])) {
            $variant->id = $mappedData['id'];
        }
        
        if (isset($mappedData['weight'])) {
            $variant->weight = (float) $mappedData['weight'];
        }
        
        if (isset($mappedData['thumbnail'])) {
            $variant->thumbnail = $mappedData['thumbnail'];
        }
        
        if (isset($mappedData['mediaList'])) {
            $variant->mediaList = $mappedData['mediaList'];
        }
        
        if (isset($mappedData['variations'])) {
            $variant->variations = $mappedData['variations'];
        }
        
        return $variant;
    }

    /**
     * Validate variant data
     * Returns validation result without throwing exceptions
     */
    public function validate(): array
    {
        return $this->validator->validate($this);
    }

    /**
     * Validate variant data and throw exception if invalid
     */
    public function validateOrFail(): void
    {
        $validation = $this->validate();
        if (!$validation['valid']) {
            throw new EkatraValidationException(
                'Variant validation failed: ' . implode(', ', $validation['errors']),
                $validation['errors']
            );
        }
    }

    /**
     * Transform variant to Ekatra format
     * Throws exception if validation fails
     */
    public function toEkatraFormat(): array
    {
        // Auto-generate variations if needed
        $this->generateVariationsFromSingleData();
        
        $this->validateOrFail();
        return $this->transformer->toEkatraFormat($this);
    }

    /**
     * Transform variant to Ekatra format with validation results
     * Returns both data and validation status
     */
    public function toEkatraFormatWithValidation(): array
    {
        // Auto-generate variations if needed
        $this->generateVariationsFromSingleData();
        
        $validation = $this->validate();
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'validation' => $validation
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->transformer->toEkatraFormat($this),
            'validation' => $validation
        ];
    }


    /**
     * Auto-generate variations from single variant data
     */
    private function generateVariationsFromSingleData(): void
    {
        // If no variations provided but we have single variant data
        if (empty($this->variations) && ($this->mrp || $this->sellingPrice || $this->quantity)) {
            $this->variations = [
                [
                    'sizeId' => $this->id ?: 'default-' . uniqid(),
                    'mrp' => (float) $this->mrp,
                    'sellingPrice' => (float) $this->sellingPrice,
                    'availability' => $this->quantity > 0,
                    'quantity' => (int) $this->quantity,
                    'size' => $this->size ?: 'freestyle',
                    'variantId' => $this->id ?: 'default-' . uniqid()
                ]
            ];
        }
    }

    /**
     * Get variant as array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'size' => $this->size,
            'quantity' => $this->quantity,
            'mrp' => $this->mrp,
            'sellingPrice' => $this->sellingPrice,
            'discountPercent' => $this->discountPercent,
            'videoUrls' => $this->videoUrls,
            'images' => $this->images,
            'weight' => $this->weight,
            'thumbnail' => $this->thumbnail,
            'mediaList' => $this->mediaList,
            'variations' => $this->variations
        ];
    }
}
