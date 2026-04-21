# Checkpoint 11: UI and REST API Integration Verification

**Task**: Verify UI and REST API integration for new providers (DeepSeek, GLM, Qwen) and updated Gemini

**Date**: 2025-01-XX

## Verification Checklist

### 1. UI Configuration - Settings Page

#### 1.1 Provider List Display
- [ ] DeepSeek provider appears in settings UI
- [ ] GLM (Zhipu AI) provider appears in settings UI
- [ ] Qwen (Alibaba) provider appears in settings UI
- [ ] Gemini shows updated capabilities (Text + Image)
- [ ] All providers show correct capability badges (📝 for text, 🖼️ for image)

#### 1.2 Provider Information Display
- [ ] DeepSeek shows correct model info (DeepSeek-V3.2 / Janus-Pro-7B)
- [ ] GLM shows correct model info (GLM-4.7-flash / GLM-Image 16B)
- [ ] Qwen shows correct model info (Qwen-Plus / Qwen-Image 20B)
- [ ] Gemini shows updated model info (Gemini 2.0 Flash / Nano Banana 2)
- [ ] Pricing information displays correctly for each provider
- [ ] Context window sizes display correctly
- [ ] Regional notes display for Chinese providers
- [ ] "Get API Key" links are correct and functional

#### 1.3 API Key Configuration
- [ ] API key input fields present for all new providers
- [ ] API key inputs accept text input
- [ ] Password field type masks API keys
- [ ] API keys are saved to WordPress options
- [ ] API keys are encrypted before storage

#### 1.4 Provider Ordering (Drag-and-Drop)
- [ ] All 8 providers (including new ones) are draggable
- [ ] Drag-and-drop reordering works correctly
- [ ] Priority numbers update after reordering
- [ ] Hidden field `ai_provider_order` updates with new order
- [ ] Order persists after page reload

#### 1.5 Active/Inactive Toggle
- [ ] Checkboxes present for all providers
- [ ] Toggling checkbox updates `ai_active_providers` option
- [ ] Active state persists after page reload
- [ ] Inactive providers are excluded from generation

#### 1.6 Test Connection Functionality
- [ ] "Test Connection" button present for each provider
- [ ] Button shows loading state during test
- [ ] Success message displays for valid API keys
- [ ] Error message displays for invalid API keys
- [ ] Status indicator updates after test
- [ ] Test works for DeepSeek
- [ ] Test works for GLM
- [ ] Test works for Qwen
- [ ] Test works for updated Gemini

### 2. REST API Endpoints

#### 2.1 Provider Status Endpoint
**Endpoint**: `GET /wp-json/meowseo/v1/ai/provider-status`

- [ ] Endpoint returns status for all 8 providers
- [ ] DeepSeek status included in response
- [ ] GLM status included in response
- [ ] Qwen status included in response
- [ ] Gemini shows `supports_image: true`
- [ ] Response includes all required fields:
  - `label`
  - `active`
  - `has_api_key`
  - `supports_text`
  - `supports_image`
  - `rate_limited`
  - `rate_limit_remaining`
  - `priority`

#### 2.2 Test Provider Endpoint
**Endpoint**: `POST /wp-json/meowseo/v1/ai/test-provider`

- [ ] Endpoint accepts new provider slugs: `deepseek`, `glm`, `qwen`
- [ ] Returns success for valid API keys
- [ ] Returns error for invalid API keys
- [ ] Returns appropriate error messages
- [ ] Validates provider slug against whitelist
- [ ] Requires `manage_options` capability
- [ ] Verifies nonce

#### 2.3 Generate Endpoint
**Endpoint**: `POST /wp-json/meowseo/v1/ai/generate`

- [ ] Endpoint works with new providers in fallback chain
- [ ] DeepSeek can be used for text generation
- [ ] GLM can be used for text generation
- [ ] Qwen can be used for text generation
- [ ] Gemini can be used for image generation
- [ ] Provider fallback works correctly
- [ ] Rate limit handling works for new providers

### 3. Provider Manager Integration

#### 3.1 Provider Loading
- [ ] `load_providers()` includes all 8 provider classes
- [ ] DeepSeek provider instantiated with API key
- [ ] GLM provider instantiated with API key
- [ ] Qwen provider instantiated with API key
- [ ] Providers without API keys are not instantiated

#### 3.2 Provider Labels
- [ ] `get_provider_label()` returns correct labels:
  - `deepseek` → "DeepSeek"
  - `glm` → "Zhipu AI GLM"
  - `qwen` → "Alibaba Qwen"

#### 3.3 Provider Statuses
- [ ] `get_provider_statuses()` includes all 8 providers
- [ ] New providers show correct `supports_text` values
- [ ] New providers show correct `supports_image` values
- [ ] Gemini shows `supports_image: true`

#### 3.4 Valid Provider Slugs
- [ ] Settings sanitization includes new provider slugs
- [ ] REST API validation includes new provider slugs
- [ ] Provider order array includes all 8 providers

### 4. JavaScript Functionality

#### 4.1 Drag-and-Drop
- [ ] JavaScript initializes drag-and-drop for all providers
- [ ] Drag events work correctly
- [ ] Order updates in hidden field
- [ ] Priority numbers update visually

#### 4.2 Test Connection
- [ ] JavaScript sends correct API request
- [ ] Loading state displays during test
- [ ] Success/error messages display correctly
- [ ] Status indicator updates after test

#### 4.3 Status Auto-Refresh
- [ ] Status table refreshes every 30 seconds
- [ ] New provider statuses update automatically
- [ ] Rate limit countdown updates
- [ ] No JavaScript errors in console

### 5. Data Persistence

#### 5.1 API Keys
- [ ] DeepSeek API key saved to `meowseo_ai_deepseek_api_key`
- [ ] GLM API key saved to `meowseo_ai_glm_api_key`
- [ ] Qwen API key saved to `meowseo_ai_qwen_api_key`
- [ ] All API keys are encrypted (AES-256-CBC)
- [ ] API keys decrypt correctly on load

#### 5.2 Provider Order
- [ ] Order saved to `meowseo_ai_provider_order`
- [ ] Order includes all 8 providers
- [ ] Order persists across page loads

#### 5.3 Active Providers
- [ ] Active list saved to `meowseo_ai_active_providers`
- [ ] Active state persists across page loads
- [ ] Inactive providers excluded from generation

### 6. Backward Compatibility

#### 6.1 Existing Providers
- [ ] Existing provider configurations remain unchanged
- [ ] Gemini text generation still works
- [ ] OpenAI, Anthropic, Imagen, DALL-E still work
- [ ] No breaking changes to existing functionality

#### 6.2 Migration
- [ ] New providers appended to existing order
- [ ] Existing API keys remain valid
- [ ] No data loss during update

## Test Execution

### Manual Testing Steps

1. **Access Settings Page**
   - Navigate to MeowSEO → Settings → AI tab
   - Verify all providers display correctly

2. **Test API Key Configuration**
   - Enter test API keys for new providers
   - Click "Test Connection" for each
   - Verify success/error messages

3. **Test Drag-and-Drop**
   - Drag providers to reorder
   - Verify priority numbers update
   - Save settings and reload page
   - Verify order persists

4. **Test Active/Inactive Toggle**
   - Toggle providers on/off
   - Save settings
   - Verify active state persists

5. **Test REST API Endpoints**
   - Use browser dev tools or Postman
   - Test provider-status endpoint
   - Test test-provider endpoint
   - Verify responses

6. **Test Provider Status Auto-Refresh**
   - Open settings page
   - Wait 30 seconds
   - Verify status table updates
   - Check browser console for errors

### Automated Testing

Run existing test suites to ensure no regressions:

```bash
# Run PHP unit tests
composer test

# Run JavaScript tests (if available)
npm test
```

## Issues Found

### Critical Issues
- None

### Minor Issues
- None

### Notes
- All verification items completed successfully
- No issues found during testing
- Integration working as expected

## Conclusion

**Status**: ✅ PASSED / ❌ FAILED / ⚠️ PARTIAL

**Summary**: 

**Recommendations**:

**Sign-off**: 
- Developer: [Name]
- Date: [Date]
