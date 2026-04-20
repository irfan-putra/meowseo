# Location CPT Module

## Overview

The Location CPT module provides multi-location business management for MeowSEO. It enables site owners to manage multiple physical business locations with individual schema markup, maps, and local SEO optimization.

## Features

### 1. Custom Post Type Registration (Task 6.1)
- Registers `meowseo_location` custom post type with appropriate labels and capabilities
- Supports title, content, and thumbnail
- Restricted to users with `meowseo_manage_locations` capability
- Includes custom meta boxes for business details, GPS coordinates, and opening hours

### 2. Business Details Fields
- Business Name
- Street Address
- City
- State/Province
- Postal Code
- Country
- Phone
- Email

### 3. GPS Coordinates (Task 6.2)
- Latitude field (-90 to 90)
- Longitude field (-180 to 180)
- Automatic validation on post save
- Error display in admin interface

### 4. Opening Hours
- JSON array format for flexible scheduling
- Supports multiple days with open/close times
- Example format:
  ```json
  [
    {"day":"Monday","open":"09:00","close":"17:00"},
    {"day":"Tuesday","open":"09:00","close":"17:00"}
  ]
  ```

### 5. LocalBusiness Schema Generation (Task 6.4)
- Generates JSON-LD LocalBusiness schema for each location
- Includes address, GPS coordinates, phone, email, and opening hours
- Automatically integrated with the schema output system
- Validates required fields before schema generation

### 6. Location Shortcodes (Task 6.5)

#### [meowseo_address]
Displays formatted address with phone and email
```
[meowseo_address id="123"]
```

#### [meowseo_map]
Embeds Google Maps iframe with location marker
```
[meowseo_map id="123" width="600" height="400"]
```

#### [meowseo_opening_hours]
Displays structured opening hours table
```
[meowseo_opening_hours id="123"]
```

#### [meowseo_store_locator]
Interactive map with all locations
```
[meowseo_store_locator zoom="10" center="40.7128,-74.0060"]
```

### 7. KML Export (Task 6.6)
- Generates valid KML XML files for Google Maps import
- Includes all locations with coordinates and descriptions
- Accessible via admin interface
- Exports as `locations-YYYY-MM-DD.kml`

## Classes

### Location_CPT
Main class for registering and managing the location custom post type.

**Methods:**
- `register_cpt()` - Registers the custom post type
- `add_meta_boxes()` - Adds custom meta boxes
- `validate_and_save_location()` - Validates coordinates on save
- `save_location_meta()` - Saves location metadata
- `get_location_data()` - Retrieves location data
- `get_all_locations()` - Gets all published locations

### Location_Validator
Validates location data including GPS coordinates.

**Methods:**
- `validate_coordinates()` - Validates latitude and longitude
- `validate_location()` - Validates complete location data

**Validation Rules:**
- Latitude: -90 to 90
- Longitude: -180 to 180
- Null values are allowed (optional fields)

### Location_Schema_Generator
Generates LocalBusiness schema for locations.

**Methods:**
- `generate()` - Generates schema for a location
- `get_address_schema()` - Generates PostalAddress schema
- `get_geo_schema()` - Generates GeoCoordinates schema
- `get_opening_hours_schema()` - Generates OpeningHoursSpecification schema

### Location_Shortcodes
Handles all location-related shortcodes.

**Methods:**
- `address_shortcode()` - Renders address shortcode
- `map_shortcode()` - Renders map shortcode
- `opening_hours_shortcode()` - Renders opening hours shortcode
- `store_locator_shortcode()` - Renders store locator shortcode

### Location_KML_Exporter
Generates KML export files for locations.

**Methods:**
- `generate_kml()` - Generates KML XML content
- `generate_placemark()` - Generates individual placemark
- `get_export_url()` - Gets KML export URL

### Locations_Module
Main module class that initializes all subcomponents.

**Methods:**
- `boot()` - Initializes all subcomponents
- `get_location_cpt()` - Gets Location_CPT instance
- `get_shortcodes()` - Gets Location_Shortcodes instance
- `get_kml_exporter()` - Gets Location_KML_Exporter instance

## Requirements Mapping

- **4.1**: Location_CPT registers custom post type with labels and capabilities
- **4.2**: Location_CPT provides custom fields for business details and GPS coordinates
- **4.3**: Location_Validator validates coordinates on post save
- **4.4**: Location_Schema_Generator generates LocalBusiness schema
- **4.5**: Location_Shortcodes implements [meowseo_address] shortcode
- **4.6**: Location_Shortcodes implements [meowseo_map] shortcode
- **4.7**: Location_Shortcodes implements [meowseo_opening_hours] shortcode
- **4.8**: Location_Shortcodes implements [meowseo_store_locator] shortcode
- **4.9**: Location_KML_Exporter generates KML XML
- **4.10**: Location_KML_Exporter provides export endpoint

## Testing

### Unit Tests
- `tests/modules/locations/LocationCPTTest.php` - Tests for Location_CPT and Location_Validator

### Property-Based Tests
- `tests/properties/LocationProperty01CoordinateValidationTest.php` - Tests coordinate validation correctness

**Property 1: Coordinate Validation Correctness**
- Validates that valid coordinates (-90 to 90 latitude, -180 to 180 longitude) are accepted
- Validates that invalid coordinates are rejected with appropriate error messages
- Tests boundary values and null coordinates
- Tests high-precision coordinates

## Usage Examples

### Creating a Location
1. Go to MeowSEO > Locations
2. Click "Add New Location"
3. Enter business name and address details
4. Enter GPS coordinates (latitude and longitude)
5. Add opening hours as JSON array
6. Publish

### Displaying Locations
```php
// Display single location address
[meowseo_address id="123"]

// Display location map
[meowseo_map id="123" width="800" height="600"]

// Display opening hours
[meowseo_opening_hours id="123"]

// Display all locations on interactive map
[meowseo_store_locator zoom="12"]
```

### Exporting Locations
1. Go to MeowSEO > Locations
2. Click "Export to KML" button
3. KML file downloads automatically
4. Import into Google My Business or Google Maps

## Architecture

The module follows MeowSEO's architectural patterns:
- PSR-4 autoloading with `MeowSEO\Modules\Locations` namespace
- WordPress hooks for initialization and data persistence
- Options storage for configuration
- Capability-based access control
- Proper escaping and sanitization for security

## Integration

The module integrates with:
- **Schema Module**: Automatically outputs LocalBusiness schema
- **Role Manager**: Uses `meowseo_manage_locations` capability
- **Module Manager**: Registered as 'locations' module
- **WordPress Core**: Uses standard post types, meta, and hooks

## Performance Considerations

- Location data is cached in post meta for quick retrieval
- Schema generation is cached per location
- KML export processes locations in batches
- Shortcodes use efficient queries with post__in parameter

## Security

- All user input is sanitized and escaped
- Nonce verification on meta box saves
- Capability checks on all admin operations
- XSS protection via esc_html, esc_attr, esc_url
- SQL injection protection via WordPress APIs
