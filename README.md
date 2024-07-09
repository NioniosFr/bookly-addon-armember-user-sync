# WordPress Plugin: Bookly Addon ARMember User Sync

This WordPress plugin serves as an addon to Bookly, bridging the gap between WordPress users created via ARMember and the corresponding Bookly customers.

## Features

- Automatic creation of Bookly customers when an ARMember registers, either manually or via admin actions.
- Cascading of updated details from ARMember to the corresponding Bookly customer.
- Deletion of the corresponding Bookly customer and all associated data when a WP User (ARMember) is deleted.

*Note:* Deleting a WP user will result in the loss of any historical data about their past bookings (same logic applies in ARMember as well).

## Installation

1. Download the `.zip` version of this GitHub repository.
2. Install the plugin via your WordPress admin plugin section.
3. Activate the plugin (ensure `ARMember` and `Bookly` are also installed and active prior to this plugin's installation).
