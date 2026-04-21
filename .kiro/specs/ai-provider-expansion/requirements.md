# Requirements Document

## Introduction

This specification defines the requirements for expanding AI provider support in MeowSEO. The expansion adds three new AI providers (DeepSeek, GLM/Zhipu AI, and Qwen/Alibaba) and enhances existing providers with image generation capabilities.

The expansion is particularly valuable for:
- **Cost optimization**: DeepSeek offers 94-97% cost reduction compared to major LLM providers
- **Regional availability**: Chinese providers offer better accessibility for users in China
- **Model diversity**: Different model strengths for various SEO content generation tasks
- **Redundancy**: Additional fallback options in the provider chain
- **Image generation expansion**: More image providers beyond DALL-E and Imagen

## Current Provider Status

### Existing Providers (Already Implemented)

| Provider | Slug | Text | Image | Status |
|----------|------|------|-------|--------|
| OpenAI | `openai` | ✅ GPT-4o-mini | ✅ DALL-E-3 | Implemented |
| Anthropic Claude | `anthropic` | ✅ Claude Haiku | ❌ | Implemented |
| Google Gemini | `gemini` | ✅ Gemini 2.0 Flash | ❌ | **Needs update** |
| DALL-E | `dalle` | ❌ | ✅ DALL-E-3 | Implemented |
| Google Imagen | `imagen` | ❌ | ✅ Imagen | Implemented |

### New Providers (This Spec)

| Provider | Slug | Text | Image | API Format |
|----------|------|------|-------|------------|
| DeepSeek | `deepseek` | ✅ DeepSeek-V3.2 | ✅ Janus-Pro-7B | OpenAI-compatible |
| GLM (Zhipu AI) | `glm` | ✅ GLM-4.7-flash | ✅ GLM-Image (16B) | OpenAI-compatible |
| Qwen (Alibaba) | `qwen` | ✅ Qwen-Plus | ✅ Qwen-Image (20B) | OpenAI-compatible |

## Glossary

- **AI_Provider_Interface**: The contract that all AI providers must implement, defining methods for text generation, image generation, API key validation, and capability reporting.
- **Provider_Manager**: The orchestrator class that manages multiple AI providers, handles fallback logic, rate limit caching, and API key encryption.
- **Provider_Slug**: A unique machine-readable identifier for each provider (e.g., 'deepseek', 'glm', 'qwen').
- **OpenAI-Compatible_API**: An API that follows the OpenAI Chat Completions format, allowing reuse of existing request/response handling logic.
- **DeepSeek**: A Chinese AI company offering frontier-level models at significantly reduced costs, with OpenAI-compatible API.
- **GLM (Zhipu AI)**: A Chinese AI company providing GLM series models with OpenAI-compatible API, offering both free and paid tiers.
- **Qwen (Alibaba)**: Alibaba's Qwen model family available through Alibaba Cloud Model Studio, with OpenAI-compatible API.
- **Nano Banana 2**: Google's codename for Gemini 3.1 Flash Image model (`gemini-3.1-flash-image-preview`), launched Feb 26, 2026.
- **GLM-Image**: Zhipu AI's 16B parameter image generation model with exceptional text rendering capabilities.
- **Qwen-Image**: Alibaba's 20B parameter image generation model with native Chinese/English text rendering.
- **Janus-Pro-7B**: DeepSeek's unified multimodal model for image understanding and generation.
- **Fallback_Chain**: The ordered sequence of providers used when attempting text or image generation, with automatic failover on errors.
- **Rate_Limit_Cache**: WordPress Object Cache entries that track provider rate limits to avoid unnecessary API calls.

## Requirements

### Requirement 1: DeepSeek Provider Implementation

**User Story:** As a MeowSEO user, I want to use DeepSeek as an AI provider, so that I can generate SEO content at a significantly lower cost while maintaining quality.

#### Acceptance Criteria

1. THE DeepSeek_Provider SHALL implement the AI_Provider_Interface with slug 'deepseek' and label 'DeepSeek'
2. THE DeepSeek_Provider SHALL support text generation using the deepseek-chat model (DeepSeek-V3.2)
3. THE DeepSeek_Provider SHALL support image generation using the janus-pro-7b model (when available via API)
4. WHEN a text generation request is made, THE DeepSeek_Provider SHALL send requests to 'https://api.deepseek.com/chat/completions' using OpenAI-compatible format
5. WHEN an API key is provided, THE DeepSeek_Provider SHALL authenticate requests using the 'Authorization: Bearer' header
6. IF the DeepSeek API returns HTTP 429, THEN THE DeepSeek_Provider SHALL throw Provider_Rate_Limit_Exception with retry-after value
7. IF the DeepSeek API returns HTTP 401, THEN THE DeepSeek_Provider SHALL throw Provider_Auth_Exception
8. WHEN validate_api_key is called, THE DeepSeek_Provider SHALL make a minimal API request to verify the key validity
9. THE DeepSeek_Provider SHALL return token usage information including input_tokens and output_tokens from the API response
10. THE DeepSeek_Provider SHALL use default temperature of 0.7 and max_tokens of 2048 for text generation

### Requirement 2: GLM (Zhipu AI) Provider Implementation

**User Story:** As a MeowSEO user, I want to use GLM models from Zhipu AI as an AI provider, so that I can access Chinese-optimized models with flexible pricing options including free tiers.

#### Acceptance Criteria

1. THE GLM_Provider SHALL implement the AI_Provider_Interface with slug 'glm' and label 'Zhipu AI GLM'
2. THE GLM_Provider SHALL support text generation using the glm-4.7-flash model (free tier) as default
3. THE GLM_Provider SHALL support image generation using the glm-image model (16B parameters)
4. WHEN a text generation request is made, THE GLM_Provider SHALL send requests to 'https://api.z.ai/api/paas/v4/chat/completions' using OpenAI-compatible format
5. WHEN an image generation request is made, THE GLM_Provider SHALL send requests to the GLM-Image API endpoint
6. WHEN an API key is provided, THE GLM_Provider SHALL authenticate requests using the 'Authorization: Bearer' header
7. IF the GLM API returns HTTP 429, THEN THE GLM_Provider SHALL throw Provider_Rate_Limit_Exception with retry-after value
8. IF the GLM API returns HTTP 401 or 403, THEN THE GLM_Provider SHALL throw Provider_Auth_Exception
9. WHEN validate_api_key is called, THE GLM_Provider SHALL make a minimal API request to verify the key validity
10. THE GLM_Provider SHALL return token usage information including input_tokens and output_tokens from the API response
11. THE GLM_Provider SHALL support optional 'thinking' mode parameter for reasoning-enhanced responses
12. THE GLM_Provider SHALL support image sizes from 512x512 to 4096x4096 pixels

### Requirement 3: Qwen (Alibaba) Provider Implementation

**User Story:** As a MeowSEO user, I want to use Qwen models from Alibaba Cloud as an AI provider, so that I can access high-performance multilingual models with strong Chinese language support.

#### Acceptance Criteria

1. THE Qwen_Provider SHALL implement the AI_Provider_Interface with slug 'qwen' and label 'Alibaba Qwen'
2. THE Qwen_Provider SHALL support text generation using the qwen-plus model as default
3. THE Qwen_Provider SHALL support image generation using the qwen-image model (20B parameters)
4. WHEN a text generation request is made, THE Qwen_Provider SHALL send requests to the Alibaba Cloud Model Studio API using OpenAI-compatible format
5. WHEN an image generation request is made, THE Qwen_Provider SHALL send requests to the Qwen-Image API endpoint via Alibaba Cloud DashScope
6. WHEN an API key is provided, THE Qwen_Provider SHALL authenticate requests using the 'Authorization: Bearer' header
7. IF the Qwen API returns HTTP 429, THEN THE Qwen_Provider SHALL throw Provider_Rate_Limit_Exception with retry-after value
8. IF the Qwen API returns HTTP 401 or 403, THEN THE Qwen_Provider SHALL throw Provider_Auth_Exception
9. WHEN validate_api_key is called, THE Qwen_Provider SHALL make a minimal API request to verify the key validity
10. THE Qwen_Provider SHALL return token usage information including input_tokens and output_tokens from the API response
11. THE Qwen_Provider SHALL support image sizes up to 3584x3584 pixels
12. THE Qwen_Provider SHALL excel at Chinese and English text rendering within generated images

### Requirement 4: Gemini Image Generation Enhancement

**User Story:** As a MeowSEO user, I want to use Gemini for image generation, so that I can leverage Google's Nano Banana 2 model for high-quality image creation.

#### Acceptance Criteria

1. THE Provider_Gemini SHALL be updated to support image generation via Nano Banana 2
2. THE Provider_Gemini SHALL use model 'gemini-3.1-flash-image-preview' for image generation
3. WHEN an image generation request is made, THE Provider_Gemini SHALL send requests to the Gemini API image generation endpoint
4. THE Provider_Gemini SHALL support image sizes from 512px to 4K (4096x4096)
5. THE Provider_Gemini SHALL support subject consistency for up to 5 characters and 14 objects
6. THE Provider_Gemini SHALL support precision text rendering and in-image translation
7. THE Provider_Gemini SHALL authenticate using the existing 'x-goog-api-key' header
8. THE Provider_Gemini SHALL return image URL and revised prompt from successful generation
9. THE supports_image() method SHALL return true after this update
10. THE Provider_Gemini SHALL maintain backward compatibility with existing text generation functionality

### Requirement 5: Provider Manager Integration

**User Story:** As a MeowSEO user, I want the new providers to be automatically available in the provider selection and fallback chain, so that I can use them seamlessly alongside existing providers.

#### Acceptance Criteria

1. THE Provider_Manager SHALL include 'deepseek', 'glm', and 'qwen' in the provider_classes array
2. WHEN get_provider_statuses is called, THE Provider_Manager SHALL return status information for all three new providers
3. WHEN get_ordered_providers is called with type 'text', THE Provider_Manager SHALL include DeepSeek, GLM, and Qwen if they have valid API keys and are active
4. WHEN get_ordered_providers is called with type 'image', THE Provider_Manager SHALL include DeepSeek, GLM, Qwen, and Gemini (after update) if they have valid API keys and are active
5. THE Provider_Manager SHALL support the new provider slugs in the 'ai_provider_order' option
6. THE Provider_Manager SHALL support the new provider slugs in the 'ai_active_providers' option
7. THE Provider_Manager SHALL store and retrieve encrypted API keys for each new provider using the pattern 'meowseo_ai_{slug}_api_key'
8. THE Provider_Manager SHALL update the get_provider_label method to include labels for new providers
9. THE Provider_Manager SHALL update the all_slugs array to include 'deepseek', 'glm', and 'qwen'

### Requirement 6: Settings UI Updates

**User Story:** As a MeowSEO administrator, I want to configure API keys and manage the new providers in the settings page, so that I can control which providers are active and their priority order.

#### Acceptance Criteria

1. WHEN the AI settings page is rendered, THE Settings_UI SHALL display API key input fields for DeepSeek, GLM, and Qwen
2. WHEN an API key is entered for a new provider, THE Settings_UI SHALL save the encrypted key to the WordPress options table
3. WHEN the 'Test Connection' button is clicked for a new provider, THE Settings_UI SHALL call the provider's validate_api_key method and display the result
4. WHEN the provider order drag-and-drop interface is rendered, THE Settings_UI SHALL include DeepSeek, GLM, and Qwen as draggable items
5. WHEN a provider's checkbox is toggled, THE Settings_UI SHALL update the 'ai_active_providers' option
6. THE Settings_UI SHALL display provider capability badges indicating 'Text + Image' for DeepSeek, GLM, Qwen, and updated Gemini
7. THE Settings_UI SHALL display pricing information hints for each new provider to help users make informed choices
8. THE Settings_UI SHALL display links to obtain API keys from DeepSeek, Zhipu AI, and Alibaba Cloud
9. THE Settings_UI SHALL show model information including context window size and default model for each provider

### Requirement 7: Error Handling and Logging

**User Story:** As a MeowSEO user, I want clear error messages when new providers fail, so that I can diagnose and resolve configuration issues.

#### Acceptance Criteria

1. WHEN a new provider returns an error, THE Provider_Manager SHALL log the error with provider slug, error message, and timestamp
2. WHEN all providers including new ones fail, THE Provider_Manager SHALL return a WP_Error with aggregated error messages
3. WHEN a new provider is rate-limited, THE Provider_Manager SHALL cache the rate limit status and skip that provider for subsequent requests
4. WHEN a new provider's API key is invalid, THE Provider_Manager SHALL include the authentication error in the error response
5. THE Logger SHALL record provider selection, attempts, successes, and failures for the new providers
6. THE error messages SHALL include actionable guidance for common issues (invalid key, rate limit, network error)

### Requirement 8: Backward Compatibility

**User Story:** As a MeowSEO user with existing configurations, I want my current provider settings to remain functional after the update, so that I experience no disruption in service.

#### Acceptance Criteria

1. WHEN the plugin is updated, THE existing provider configurations SHALL remain unchanged
2. WHEN no API key is configured for a new provider, THE Provider_Manager SHALL NOT attempt to instantiate that provider
3. WHEN the 'ai_provider_order' option does not include new providers, THE Provider_Manager SHALL append new providers at the end of the order
4. THE existing provider slugs ('gemini', 'openai', 'anthropic', 'imagen', 'dalle') SHALL continue to function without modification
5. THE AI_Provider_Interface SHALL remain unchanged to ensure all existing providers continue to work
6. THE Gemini text generation functionality SHALL remain backward compatible after adding image support

### Requirement 9: Documentation and Help Text

**User Story:** As a MeowSEO user, I want documentation explaining the new providers and their characteristics, so that I can choose the best provider for my needs.

#### Acceptance Criteria

1. THE Settings_UI SHALL display help text explaining each new provider's strengths and pricing model
2. THE Settings_UI SHALL provide links to official documentation for obtaining API keys from DeepSeek, Zhipu AI, and Alibaba Cloud
3. THE Settings_UI SHALL display regional availability information for each new provider
4. THE Settings_UI SHALL show model information including context window size and default model for each provider
5. THE documentation SHALL include a comparison table of all providers with text and image capabilities
6. THE documentation SHALL explain the fallback chain behavior and how to configure provider priority

### Requirement 10: Testing Requirements

**User Story:** As a MeowSEO developer, I want comprehensive tests for the new providers, so that I can ensure reliability and catch regressions early.

#### Acceptance Criteria

1. THE test suite SHALL include unit tests for each new provider class (DeepSeek, GLM, Qwen)
2. THE test suite SHALL include integration tests for Provider_Manager with new providers
3. THE test suite SHALL mock API responses for testing error handling (429, 401, 403, 500)
4. THE test suite SHALL test API key validation for each new provider
5. THE test suite SHALL test rate limit caching for new providers
6. THE test suite SHALL test the fallback chain with new providers included
7. THE test suite SHALL verify backward compatibility with existing configurations

## Provider Comparison Matrix

### Text Generation Capabilities

| Provider | Model | Context Window | Cost (per 1M tokens) | Free Tier |
|----------|-------|----------------|---------------------|-----------|
| OpenAI | GPT-4o-mini | 128K | $0.15/$0.60 | No |
| Anthropic | Claude Haiku | 200K | $0.25/$1.25 | No |
| Gemini | Gemini 2.0 Flash | 1M | $0.10/$0.40 | Yes (limited) |
| DeepSeek | DeepSeek-V3.2 | 128K | $0.07/$0.28 | Yes (limited) |
| GLM | GLM-4.7-flash | 128K | $0.014/$0.014 | Yes |
| Qwen | Qwen-Plus | 128K | $0.40/$2.00 | No |

### Image Generation Capabilities

| Provider | Model | Max Resolution | Cost (per image) | Text in Image |
|----------|-------|----------------|------------------|---------------|
| OpenAI | DALL-E-3 | 1792x1024 | $0.040 | Good |
| Imagen | Imagen 3 | 1024x1024 | $0.020 | Good |
| Gemini | Nano Banana 2 | 4096x4096 | $0.045-$0.150 | Excellent |
| GLM | GLM-Image | 4096x4096 | ~$0.02 | Excellent |
| Qwen | Qwen-Image | 3584x3584 | ~$0.03 | Excellent |
| DeepSeek | Janus-Pro-7B | 1024x1024 | Varies | Good |

## Implementation Priority

1. **Phase 1: New Text Providers** - DeepSeek, GLM, Qwen text generation
2. **Phase 2: Gemini Image Update** - Add Nano Banana 2 support
3. **Phase 3: New Image Providers** - GLM-Image, Qwen-Image integration
4. **Phase 4: DeepSeek Image** - Janus-Pro-7B (if API available)
5. **Phase 5: Settings UI** - Complete UI for all new providers
