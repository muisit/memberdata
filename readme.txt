=== Member-Data ===
Contributors: muisit
Tags: data management
Requires at least: 6.1
Tested up to: 6.3
Stable tag: trunk
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Generic data management of row based entities.

== Description ==

# MemberData

This is a generic Wordpress plugin to manage data of non-site users, hypothetical 'members' of an external organisation.
It features a grid interface for reading the collected data and a configurable set of attributes, with validation rules,
for each data entry. The collected data can be downloaded to a spreadsheet.

## Plugin Interface

The plugin defines the following filters and actions on which to interface:

- `memberdata_loaded()`: plugin has registered all its callbacks and classes. Hook into this to load dependent plugins
- `memberdata_attribute_types(array $types)`: a filter that returns a dictionary of attribute type settings. The key is
   the machine name for the type as used in attributes, the values is a list of type settings: `label`, optional `rules`, 
   an `options` type field determining the type entry field for the options and an `optdefault` setting to fill the 
   `options` with a default if no value is given. If no `options` are provided, the `options` are not available for
   entry in the front-end.
- `memberdata_configuration($configuration)`: this assembles an array of attributes. The order of the array determines
   the display order in the front end interface. Each entry has a `name`, a `type`, an optional `rules` list filled with
   the default settings of the type and a `filter` boolean value ('Y' or 'N') indicating if this attribute ca be used
   to filter in the front end display.
- `memberdata_find_members(array $settings)`: this filter assembles a list of members and their attribute values. Hook into
   this to add new attributes or adjust existing attributes. The core assembly is done at priority `500`.
   The `$settings` array can contain fields for `offset` and `pagesize` (`0` or `null` means no paging), `sorter` 
   (attribute name) and `sortDirection` (`asc` or `desc`), a `filter` object (dictionary containing objects with a 
   `search` entry for free-text-search, and/or a `values` entry containing a list of specific values to filter on, and/or
   a value 'withTrashed' indicating the soft-deleted members should be searched as well), 
   a `cutoff` value indicating at which count paging can be disregarded, a `list` value containing the found members (merge
   with this list) and a `count` value containing the total count of all available members.
- `memberdata_save_member(Member: $member)`: this is called when a client wants to add a new member. Hook into
   this to create dependent structures for new members. The basic creation of the new member is done at priority `500`,
   any filter before that can adjust the member model to be saved, any filter after that can use the newly saved model
   (with a database identifier).
- `memberdata_save_attributes(array $settings)`: this is called when a client wants to save a single or a set of attributes.
   The settings array consists of `member` (Member model), `attributes` (dictionary of attribute->value), `messages`
   (array of validation messages) and `config` (array of supported attributes, as returned by `memberdata_configuration`, optional)
- `memberdata_values($settings)`: filter to retrieve a set of unique values for a specific attribute field. The `settings`
   array contains an entry `field` to indicate the attribute name and an entry `values` which contains the result list.

## Validation

Each attribute of each row can contain a list of validation rules, separated by bars ('|'). Some rules can have a parameter, 
which is specified like: 'max=12' or 'lte=30.2'. Rules are applied after converting the value using any formatting specified
in the options field, if applicable.

The following rules are currently available:

### Transformation Rules

- int: value is converted to a whole number (integer)
- float: value is converted to a floating-point number
- bool: value is set to 'Y' for entries containing the text 'yes', 'true', 'on', 't', 'y' (case insensitive), and set to 'N' otherwise
- trim: whitespace is trimmed from the value left and right
- upper: value is converted to upper case
- lower: value is converted to lower case
- ucfirst: first letter is converted to upper case

### Length or Size Rules

- lte: value has a length (text), value (numeric) or date less than or equal to the parameter
- lt: value has a length (text), value (numeric) or date less than the parameter
- eq: value has a length (text), value (numeric) or date equal to the parameter
- gt: value has a length (text), value (numeric) or date greater than the parameter
- gte: value has a length (text), value (numeric) or date greater than or equal to the parameter
- min: alias for 'gte'
- max: alias for 'lte'

### Other Rules

- required: marks the field as a required entry, cannot be left blank
- nullable: marks the field as not-required, allowed blank (same as leaving out 'required')
- fail: always fails field validation (not usable in this context)
- skip: skip validation on this field
- email: value is validated to be an e-mail address
- url: value is validated to be a URL/website address
- date: value is validated to be a date, input field uses the format specified in the options
- datetime: value is validated to be a date + time (after 1970-01-01), input field uses the format specified in the options
- enum: value is one of a list of allowed values, input field uses the bar-separated list in the options

== Frequently Asked Questions ==

= Can I connect a contact form to the collection? =

Not at the moment and not through this plugin. A dependent plugin is planned to manage forms for data collection.

= Can I import data using a spreadsheet? =

Not yet.

== Changelog ==

= 1.0 =
Initial version
