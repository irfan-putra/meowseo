# Migration Guide: Yoast SEO / Rank Math to MeowSEO

This guide helps you migrate from Yoast SEO or Rank Math to MeowSEO while preserving your SEO data and settings.

## Table of Contents

- [Before You Start](#before-you-start)
- [Migration Process](#migration-process)
- [Data Mapping](#data-mapping)
- [Feature Comparison](#feature-comparison)
- [Post-Migration Checklist](#post-migration-checklist)
- [Troubleshooting](#troubleshooting)

## Before You Start

### Prerequisites

- **Backup Your Site**: Create a full backup of your database and files
- **PHP Version**: Ensure PHP 8.0 or higher
- **WordPress Version**: Ensure WordPress 6.0 or higher
- **Test Environment**: Test migration on staging site first

### What Gets Migrated

✅ **Migrated:**
- SEO titles and descriptions
- Focus keywords
- Robots directives (noindex, nofollow)
- Canonical URLs
- Open Graph data
- Twitter Card data
- Schema markup settings
- Breadcrumb settings

❌ **Not Migrated:**
- Redirects (manual recreation required)
- 404 logs (fresh start)
- Search Console data (reconnect required)
- Premium features (if applicable)

## Migration Process

### Step 1: Install MeowSEO

1. Download MeowSEO plugin
2. Upload to `/wp-content/plugins/meowseo/`
3. **Do NOT activate yet**

### Step 2: Export Current Settings (Optional)

**For Yoast SEO:**
```bash
# Export via WP-CLI
wp yoast export-settings > yoast-settings.json
```

**For Rank Math:**
```bash
# Export via admin panel
# Go to Rank Math > Status & Tools > Import & Export
# Click "Export Settings"
```

### Step 3: Run Migration Script

Activate MeowSEO and run the migration:

```bash
# Activate MeowSEO
wp plugin activate meowseo

# Run migration from Yoast
wp meowseo migrate --from=yoast

# Or from Rank Math
wp meowseo migrate --from=rankmath
```

**Migration Options:**

```bash
# Dry run (preview without changes)
wp meowseo migrate --from=yoast --dry-run

# Migrate specific post types only
wp meowseo migrate --from=yoast --post-types=post,page

# Skip schema migration
wp meowseo migrate --from=yoast --skip-schema

# Verbose output
wp meowseo migrate --from=yoast --verbose
```

### Step 4: Verify Migration

Check migration results:

```bash
# View migration report
wp meowseo migrate --report

# Check specific post
wp meowseo meta get 123
```

### Step 5: Deactivate Old Plugin

**Important:** Only deactivate after verifying migration success.

```bash
# Deactivate Yoast
wp plugin deactivate wordpress-seo

# Or deactivate Rank Math
wp plugin deactivate seo-by-rank-math
```

**Do NOT delete the old plugin yet** - keep it for 30 days in case you need to rollback.

## Data Mapping

### Yoast SEO to MeowSEO

| Yoast Meta Key | MeowSEO Meta Key | Notes |
|----------------|------------------|-------|
| `_yoast_wpseo_title` | `_meowseo_title` | Direct mapping |
| `_yoast_wpseo_metadesc` | `_meowseo_description` | Direct mapping |
| `_yoast_wpseo_focuskw` | `_meowseo_focus_keyword` | Direct mapping |
| `_yoast_wpseo_meta-robots-noindex` | `_meowseo_noindex` | Converted to boolean |
| `_yoast_wpseo_meta-robots-nofollow` | `_meowseo_nofollow` | Converted to boolean |
| `_yoast_wpseo_canonical` | `_meowseo_canonical` | Direct mapping |
| `_yoast_wpseo_opengraph-title` | `_meowseo_og_title` | Direct mapping |
| `_yoast_wpseo_opengraph-description` | `_meowseo_og_description` | Direct mapping |
| `_yoast_wpseo_opengraph-image` | `_meowseo_og_image` | Direct mapping |
| `_yoast_wpseo_twitter-title` | `_meowseo_twitter_title` | Direct mapping |
| `_yoast_wpseo_twitter-description` | `_meowseo_twitter_description` | Direct mapping |
| `_yoast_wpseo_twitter-image` | `_meowseo_twitter_image` | Direct mapping |

### Rank Math to MeowSEO

| Rank Math Meta Key | MeowSEO Meta Key | Notes |
|-------------------|------------------|-------|
| `rank_math_title` | `_meowseo_title` | Direct mapping |
| `rank_math_description` | `_meowseo_description` | Direct mapping |
| `rank_math_focus_keyword` | `_meowseo_focus_keyword` | Direct mapping |
| `rank_math_robots` | `_meowseo_noindex`, `_meowseo_nofollow` | Split into separate fields |
| `rank_math_canonical_url` | `_meowseo_canonical` | Direct mapping |
| `rank_math_facebook_title` | `_meowseo_og_title` | Direct mapping |
| `rank_math_facebook_description` | `_meowseo_og_description` | Direct mapping |
| `rank_math_facebook_image` | `_meowseo_og_image` | Direct mapping |
| `rank_math_twitter_title` | `_meowseo_twitter_title` | Direct mapping |
| `rank_math_twitter_description` | `_meowseo_twitter_description` | Direct mapping |
| `rank_math_twitter_image` | `_meowseo_twitter_image` | Direct mapping |
| `rank_math_schema_Article` | `_meowseo_schema_type` | Converted to schema type |

### Global Settings

**Yoast to MeowSEO:**

| Yoast Option | MeowSEO Option | Notes |
|--------------|----------------|-------|
| `wpseo_titles` | `meowseo_title_patterns` | Title templates |
| `wpseo_social` | `meowseo_social_profiles` | Social media URLs |
| `wpseo` | `meowseo_schema_organization_name` | Organization name |
| `wpseo` | `meowseo_schema_organization_logo` | Organization logo |

**Rank Math to MeowSEO:**

| Rank Math Option | MeowSEO Option | Notes |
|------------------|----------------|-------|
| `rank-math-options-titles` | `meowseo_title_patterns` | Title templates |
| `rank-math-options-general` | `meowseo_schema_organization_name` | Organization name |
| `rank-math-options-general` | `meowseo_social_profiles` | Social media URLs |

## Feature Comparison

### Schema / Structured Data

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| Article Schema | ✅ | ✅ | ✅ |
| WebPage Schema | ✅ | ✅ | ✅ |
| Organization Schema | ✅ | ✅ | ✅ |
| Product Schema | ✅ Premium | ✅ | ✅ |
| FAQ Schema | ✅ Premium | ✅ | ✅ |
| HowTo Schema | ✅ Premium | ✅ | ⚠️ Planned |
| LocalBusiness Schema | ✅ Premium | ✅ | ⚠️ Planned |
| @graph Format | ✅ | ✅ | ✅ |
| Custom Schema | ❌ | ✅ | ✅ Via filters |

### XML Sitemaps

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| Basic Sitemap | ✅ | ✅ | ✅ |
| Image Sitemap | ✅ Premium | ✅ | ✅ |
| Video Sitemap | ✅ Premium | ✅ | ✅ |
| News Sitemap | ✅ Premium | ✅ | ✅ |
| Sitemap Index | ✅ | ✅ | ✅ |
| Pagination | ✅ | ✅ | ✅ |
| Cache Strategy | Transients | Transients | Filesystem + Lock |
| Performance | Good | Good | Excellent |

### Meta Tags

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| Title Tag | ✅ | ✅ | ✅ |
| Meta Description | ✅ | ✅ | ✅ |
| Robots Directives | ✅ | ✅ | ✅ |
| Canonical URL | ✅ | ✅ | ✅ |
| Open Graph | ✅ | ✅ | ✅ |
| Twitter Cards | ✅ | ✅ | ✅ |
| Title Templates | ✅ | ✅ | ✅ |

### Content Analysis

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| SEO Score | ✅ | ✅ | ✅ |
| Readability Score | ✅ | ✅ | ✅ |
| Focus Keyword | ✅ | ✅ | ✅ |
| Keyword Density | ✅ | ✅ | ✅ |
| Internal Links | ✅ Premium | ✅ | ✅ |
| Content AI | ✅ Premium | ❌ | ❌ |

### Redirects

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| 301 Redirects | ✅ Premium | ✅ | ✅ |
| 302 Redirects | ✅ Premium | ✅ | ✅ |
| Regex Redirects | ✅ Premium | ✅ | ✅ |
| Redirect Import | ✅ Premium | ✅ | ⚠️ Manual |

### 404 Monitoring

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| 404 Logging | ✅ Premium | ✅ | ✅ |
| Referrer Tracking | ✅ Premium | ✅ | ✅ |
| Redirect Suggestions | ✅ Premium | ✅ | ❌ |
| Performance | Good | Good | Excellent (buffered) |

### Search Console

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| GSC Integration | ✅ Premium | ✅ | ✅ |
| Performance Data | ✅ Premium | ✅ | ✅ |
| Gutenberg Panel | ✅ Premium | ✅ | ✅ |
| Rate Limiting | ✅ | ✅ | ✅ Advanced |

### Headless / API

| Feature | Yoast | Rank Math | MeowSEO |
|---------|-------|-----------|---------|
| REST API | ✅ | ✅ | ✅ |
| WPGraphQL | ✅ | ✅ | ✅ |
| Headless Support | ⚠️ Limited | ⚠️ Limited | ✅ Full |

## Post-Migration Checklist

### Immediate (Day 1)

- [ ] Verify homepage meta tags
- [ ] Check 5-10 sample posts for correct meta
- [ ] Test sitemap.xml accessibility
- [ ] Verify schema markup with Google Rich Results Test
- [ ] Check breadcrumbs display (if used)
- [ ] Test Gutenberg sidebar functionality

### Week 1

- [ ] Monitor search rankings (expect minor fluctuations)
- [ ] Check Google Search Console for errors
- [ ] Verify all post types have correct meta
- [ ] Test WooCommerce products (if applicable)
- [ ] Review 404 monitor logs
- [ ] Check internal links module

### Week 2-4

- [ ] Compare traffic to pre-migration baseline
- [ ] Review rich results in Google Search Console
- [ ] Check for any crawl errors
- [ ] Verify sitemap submission in GSC
- [ ] Monitor page speed (should improve)

### Month 2

- [ ] Delete old plugin if everything is stable
- [ ] Remove old plugin database tables (optional)
- [ ] Document any custom configurations
- [ ] Train team on MeowSEO interface

## Troubleshooting

### Migration Failed

**Error: "Database connection failed"**
- Check database credentials
- Verify database user has write permissions
- Increase PHP memory limit

**Error: "Old plugin not found"**
- Ensure old plugin is still installed (not deleted)
- Check plugin slug matches exactly
- Try manual migration (see below)

### Missing Meta Data

**Some posts missing titles/descriptions:**

```bash
# Re-run migration for specific posts
wp meowseo migrate --from=yoast --post-ids=123,456,789

# Or migrate by post type
wp meowseo migrate --from=yoast --post-types=post
```

### Schema Not Appearing

**Check schema output:**

```bash
# Generate schema for specific post
wp meowseo schema generate 123

# Validate schema
wp meowseo schema validate 123
```

**Common issues:**
- Schema module not enabled
- Post type not supported
- Cache not cleared

### Sitemap Issues

**Sitemap returns 404:**

```bash
# Flush rewrite rules
wp rewrite flush

# Regenerate sitemap
wp meowseo sitemap generate
```

**Sitemap empty:**

```bash
# Check for noindex posts
wp post list --post_type=post --meta_key=_meowseo_noindex --meta_value=1

# Clear sitemap cache
wp meowseo sitemap clear-cache
```

### Performance Degradation

**Site slower after migration:**

1. Enable Object Cache (Redis/Memcached)
2. Clear all caches (page cache, object cache, opcode cache)
3. Check for slow database queries
4. Disable unused modules

**Database queries increased:**

1. Verify indexes exist on postmeta table
2. Enable Object Cache
3. Check for N+1 query patterns

## Manual Migration

If automated migration fails, you can migrate manually:

### Step 1: Export Old Data

```sql
-- Export Yoast meta
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE meta_key LIKE '_yoast_wpseo_%'
INTO OUTFILE '/tmp/yoast_meta.csv';

-- Export Rank Math meta
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta 
WHERE meta_key LIKE 'rank_math_%'
INTO OUTFILE '/tmp/rankmath_meta.csv';
```

### Step 2: Transform Data

Use the mapping tables above to transform meta keys.

### Step 3: Import to MeowSEO

```php
// Example: Import titles
$posts = get_posts(['numberposts' => -1]);
foreach ($posts as $post) {
    $yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
    if ($yoast_title) {
        update_post_meta($post->ID, '_meowseo_title', $yoast_title);
    }
}
```

## Rollback Procedure

If you need to rollback:

### Step 1: Deactivate MeowSEO

```bash
wp plugin deactivate meowseo
```

### Step 2: Reactivate Old Plugin

```bash
# Reactivate Yoast
wp plugin activate wordpress-seo

# Or reactivate Rank Math
wp plugin activate seo-by-rank-math
```

### Step 3: Restore Backup (if needed)

```bash
# Restore database
wp db import backup.sql

# Restore files
# Use your backup solution
```

### Step 4: Clear All Caches

```bash
wp cache flush
wp rewrite flush
```

## Getting Help

### Support Channels

- **Documentation**: [README.md](README.md)
- **GitHub Issues**: [Report a bug](https://github.com/akbarbahaulloh/meowseo/issues)
- **GitHub Discussions**: [Ask a question](https://github.com/akbarbahaulloh/meowseo/discussions)

### Before Asking for Help

Please provide:

1. Migration command used
2. Error messages (full text)
3. PHP version
4. WordPress version
5. Old plugin version
6. Number of posts being migrated
7. Any custom code or plugins that might interfere

### Common Questions

**Q: Will my search rankings drop?**
A: Minor fluctuations are normal during any SEO plugin change. Rankings should stabilize within 2-4 weeks.

**Q: Can I run both plugins simultaneously?**
A: Not recommended. This will cause duplicate meta tags and schema markup.

**Q: What happens to my premium features?**
A: MeowSEO includes many features that are premium in Yoast/Rank Math. Check the feature comparison table.

**Q: How long does migration take?**
A: Depends on site size. Typical times:
- Small site (< 1,000 posts): 1-5 minutes
- Medium site (1,000-10,000 posts): 5-30 minutes
- Large site (> 10,000 posts): 30-120 minutes

**Q: Is the migration reversible?**
A: Yes, if you keep the old plugin installed and have a backup. MeowSEO doesn't delete old plugin data.

## Best Practices

### Before Migration

1. **Test on staging first**
2. **Create full backup**
3. **Document current settings**
4. **Export old plugin settings**
5. **Note any custom code**

### During Migration

1. **Use dry-run first**
2. **Monitor for errors**
3. **Verify sample posts**
4. **Check critical pages**
5. **Test all post types**

### After Migration

1. **Keep old plugin for 30 days**
2. **Monitor search rankings**
3. **Check Google Search Console**
4. **Verify rich results**
5. **Test all functionality**

## Conclusion

Migrating from Yoast SEO or Rank Math to MeowSEO is straightforward with the automated migration tool. The process preserves your SEO data while giving you access to MeowSEO's performance optimizations and modern architecture.

For most sites, the migration process takes less than 30 minutes and can be completed without any downtime.

If you encounter any issues, refer to the troubleshooting section or reach out for support.
