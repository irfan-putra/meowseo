# Implementation Plan: AI Provider Expansion

## Overview

This implementation adds three new AI providers (DeepSeek, GLM/Zhipu AI, and Qwen/Alibaba) and enhances the existing Gemini provider with image generation capabilities via Nano Banana 2. The design leverages an abstract `Provider_OpenAI_Compatible` base class to maximize code reuse across the OpenAI-compatible APIs.

## Tasks

- [x] 1. Create abstract Provider_OpenAI_Compatible base class
  - Create `includes/modules/ai/providers/class-provider-open-ai-compatible.php`
  - Implement abstract methods for API URLs, models, and provider identity
  - Implement shared `generate_text()` with OpenAI-compatible request format
  - Implement shared `generate_image()` with OpenAI-compatible request format
  - Implement shared `validate_api_key()` with minimal test request
  - Implement shared error handling (`parse_text_response`, `parse_image_response`, `handle_error_codes`)
  - Handle HTTP 429 rate limit exceptions with retry-after support
  - Handle HTTP 401/403 authentication exceptions
  - _Requirements: 1.4-1.6, 2.4-2.8, 3.4-3.8_

- [x] 2. Implement Provider_DeepSeek class
  - [x] 2.1 Create Provider_DeepSeek extending Provider_OpenAI_Compatible
    - Create `includes/modules/ai/providers/class-provider-deepseek.php`
    - Set API base URL to `https://api.deepseek.com/v1`
    - Set text model to `deepseek-chat` (DeepSeek-V3.2)
    - Set image model to `janus-pro-7b`
    - Implement `get_slug()` returning `'deepseek'`
    - Implement `get_label()` returning `'DeepSeek'`
    - _Requirements: 1.1-1.3_

  - [ ]* 2.2 Write unit tests for Provider_DeepSeek
    - Test text generation with mocked successful response
    - Test image generation with mocked successful response
    - Test HTTP 429 rate limit handling
    - Test HTTP 401 authentication error handling
    - Test API key validation
    - Test empty response handling
    - _Requirements: 1.4-1.10_

- [x] 3. Implement Provider_GLM class
  - [x] 3.1 Create Provider_GLM extending Provider_OpenAI_Compatible
    - Create `includes/modules/ai/providers/class-provider-glm.php`
    - Set API base URL to `https://api.z.ai/api/paas/v4`
    - Set text model to `glm-4.7-flash`
    - Set image model to `glm-image`
    - Implement `get_slug()` returning `'glm'`
    - Implement `get_label()` returning `'Zhipu AI GLM'`
    - Override `generate_image()` to support 512x512 to 4096x4096 sizes
    - _Requirements: 2.1-2.3, 2.12_

  - [ ]* 3.2 Write unit tests for Provider_GLM
    - Test text generation with mocked successful response
    - Test image generation with various size options
    - Test HTTP 429 rate limit handling
    - Test HTTP 401/403 authentication error handling
    - Test API key validation
    - Test thinking mode parameter (if implemented)
    - _Requirements: 2.4-2.11_

- [x] 4. Implement Provider_Qwen class
  - [x] 4.1 Create Provider_Qwen extending Provider_OpenAI_Compatible
    - Create `includes/modules/ai/providers/class-provider-qwen.php`
    - Set API base URL to `https://dashscope.aliyuncs.com/compatible-mode/v1`
    - Set image API URL to `https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis`
    - Set text model to `qwen-plus`
    - Set image model to `qwen-image`
    - Implement `get_slug()` returning `'qwen'`
    - Implement `get_label()` returning `'Alibaba Qwen'`
    - Override `get_auth_headers()` for X-DashScope-Authorization header
    - Support image sizes up to 3584x3584 pixels
    - _Requirements: 3.1-3.3, 3.11_

  - [ ]* 4.2 Write unit tests for Provider_Qwen
    - Test text generation with mocked successful response
    - Test image generation with DashScope-specific headers
    - Test HTTP 429 rate limit handling
    - Test HTTP 401/403 authentication error handling
    - Test API key validation
    - Test image size limits
    - _Requirements: 3.4-3.12_

- [x] 5. Checkpoint - Verify new provider classes load correctly
  - Ensure all three provider classes can be instantiated
  - Verify each provider returns correct slug, label, and capabilities
  - Run unit tests for all three providers
  - Ask the user if questions arise.

- [x] 6. Update Provider_Gemini for image generation support
  - [x] 6.1 Add image generation constants and update supports_image()
    - Add `IMAGE_API_URL` constant for `gemini-3.1-flash-image-preview`
    - Add `DEFAULT_IMAGE_MODEL` constant
    - Change `supports_image()` to return `true`
    - _Requirements: 4.1-4.3, 4.9_

  - [x] 6.2 Implement generate_image() method for Gemini
    - Build Gemini-specific request body with prompt text
    - Support size options (512px to 4096x4096)
    - Configure output options (mimeType: image/png)
    - Use existing `x-goog-api-key` authentication
    - Set 90-second timeout for image generation
    - _Requirements: 4.2-4.5_

  - [x] 6.3 Implement parse_image_response() for Gemini
    - Handle HTTP 429 rate limit with Provider_Rate_Limit_Exception
    - Handle HTTP 401/403 with Provider_Auth_Exception
    - Extract image URL from `body['images'][0]['url']` or `body['generatedImages'][0]['image']['url']`
    - Return URL and revised prompt
    - _Requirements: 4.7-4.8_

  - [ ]* 6.4 Write unit tests for Gemini image generation
    - Test successful image generation with mocked response
    - Test various size configurations
    - Test rate limit handling
    - Test authentication error handling
    - Test empty response handling
    - Verify backward compatibility with text generation
    - _Requirements: 4.1-4.10_

- [x] 7. Update AI_Provider_Manager to integrate new providers
  - [x] 7.1 Add new provider classes to provider_classes array
    - Add `Provider_DeepSeek`, `Provider_GLM`, `Provider_Qwen` to imports
    - Add entries for `'deepseek'`, `'glm'`, `'qwen'` in provider_classes
    - _Requirements: 5.1_

  - [x] 7.2 Update get_provider_label() method
    - Add labels for `'deepseek'` → `'DeepSeek'`
    - Add labels for `'glm'` → `'Zhipu AI GLM'`
    - Add labels for `'qwen'` → `'Alibaba Qwen'`
    - _Requirements: 5.8_

  - [x] 7.3 Update all_slugs array in get_provider_statuses()
    - Add `'deepseek'`, `'glm'`, `'qwen'` to all_slugs array
    - Update supports_text and supports_image logic for new providers
    - Update supports_image for `'gemini'` to return `true`
    - _Requirements: 5.9, 4.9_

  - [x] 7.4 Verify provider ordering and active status handling
    - Ensure new providers work with `ai_provider_order` option
    - Ensure new providers work with `ai_active_providers` option
    - Ensure API key storage pattern `meowseo_ai_{slug}_api_key` works
    - _Requirements: 5.3-5.7_

  - [ ]* 7.5 Write integration tests for Provider Manager with new providers
    - Test provider loading with API keys
    - Test get_ordered_providers() includes new providers
    - Test get_provider_statuses() returns all new providers
    - Test rate limit caching for new providers
    - Test fallback chain with new providers
    - _Requirements: 5.1-5.9_

- [x] 8. Checkpoint - Verify Provider Manager integration
  - Ensure all providers load correctly with API keys
  - Verify provider ordering works with new providers
  - Verify provider status endpoint returns correct data
  - Run integration tests
  - Ask the user if questions arise.

- [x] 9. Update AI_Settings for new provider configuration UI
  - [x] 9.1 Add API key input fields for new providers
    - Add DeepSeek API key input field
    - Add GLM (Zhipu AI) API key input field
    - Add Qwen (Alibaba Cloud) API key input field
    - _Requirements: 6.1_

  - [x] 9.2 Add provider capability badges
    - Display "Text + Image" badge for DeepSeek, GLM, Qwen
    - Update Gemini badge to show "Text + Image"
    - _Requirements: 6.6_

  - [x] 9.3 Add provider information and help text
    - Add pricing information hints for each new provider
    - Add links to obtain API keys (DeepSeek, Zhipu AI, Alibaba Cloud)
    - Add model information (context window, default model)
    - Add regional availability notes
    - _Requirements: 6.7-6.9_

  - [x] 9.4 Update provider order drag-and-drop interface
    - Include DeepSeek, GLM, Qwen as draggable items
    - Ensure toggle checkbox updates `ai_active_providers` option
    - _Requirements: 6.4-6.5_

  - [x] 9.5 Implement Test Connection functionality
    - Call provider's `validate_api_key()` method on Test button click
    - Display success/error result to user
    - _Requirements: 6.3_

- [x] 10. Update AI_REST for new provider endpoints
  - [x] 10.1 Update valid providers list in REST endpoints
    - Add `'deepseek'`, `'glm'`, `'qwen'` to valid provider slugs
    - Update provider instance factory method if needed
    - _Requirements: 5.1-5.9_

  - [x] 10.2 Verify test-provider endpoint works with new providers
    - Ensure API key validation endpoint accepts new provider slugs
    - Return appropriate error messages for invalid keys
    - _Requirements: 7.4_

- [x] 11. Checkpoint - Verify UI and REST API integration
  - Test API key configuration in settings UI
  - Test provider ordering drag-and-drop
  - Test connection validation for each new provider
  - Verify REST API endpoints return correct provider data
  - Ask the user if questions arise.

- [x] 12. Implement error handling and logging
  - [x] 12.1 Verify error logging for new providers
    - Ensure errors include provider slug, message, and timestamp
    - Log provider selection, attempts, successes, and failures
    - _Requirements: 7.1, 7.5_

  - [x] 12.2 Verify aggregated error responses
    - Ensure WP_Error includes all provider errors when all fail
    - Include actionable guidance in error messages
    - _Requirements: 7.2, 7.6_

  - [x] 12.3 Verify rate limit caching for new providers
    - Cache rate limit status when provider returns HTTP 429
    - Skip rate-limited providers in subsequent requests
    - _Requirements: 7.3_

- [x] 13. Verify backward compatibility
  - [x] 13.1 Test existing provider configurations
    - Verify existing API keys remain valid
    - Verify existing provider order is preserved
    - Verify existing active providers list is preserved
    - _Requirements: 8.1, 8.4_

  - [x] 13.2 Test new provider migration behavior
    - Verify new providers appended to order if not present
    - Verify providers without API keys are not instantiated
    - _Requirements: 8.2-8.3_

  - [x] 13.3 Verify AI_Provider interface stability
    - Ensure all existing providers continue to work
    - Ensure Gemini text generation remains backward compatible
    - _Requirements: 8.5-8.6_

- [x] 14. Final checkpoint - Complete integration verification
  - Run all unit tests
  - Run all integration tests
  - Verify backward compatibility tests pass
  - Test complete generation flow with each new provider
  - Test fallback chain with mixed provider availability
  - Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The design uses PHP (WordPress plugin context) - no language selection needed
- Property-based tests are not included as the design document explicitly states they are not applicable for this I/O-bound, external API integration feature
- Unit tests use mocked HTTP responses to avoid external service dependencies
- Integration tests verify the complete flow from REST API to provider
