# Filament and Livewire 3 Development Notes

## Filament Widget System

### 1. Widget Component Structure

-   Each widget requires both a PHP class and a Blade view template
-   Widget class names must exactly match their registration in AdminPanelProvider
-   View names in the widget must match the actual blade template path
-   Example structure:
    ```php
    class MyWidget extends Widget implements HasForms
    {
        protected static string $view = 'filament.widgets.my-widget';
    }
    ```

### 2. Widget State Management

-   Widgets can modify application state (database, session, etc.)
-   Use `protected static ?string $pollingInterval = '10s';` for auto-refresh
-   State can be shared between widgets using Livewire events
-   Example of dispatching events:
    ```php
    $this->dispatch('my-event', data: $value);
    ```

### 3. Form Handling in Widgets

-   Use `InteractsWithForms` trait for form functionality
-   Forms are defined in `getFormSchema()`
-   Live updates use the `live()` method and `afterStateUpdated`
-   Example:
    ```php
    Select::make('field')
        ->live()
        ->afterStateUpdated(function ($state) {
            // Handle state change
        })
    ```

## Livewire 3 Features

### 1. Event Handling

-   Use `#[On('event-name')]` attribute for event listeners
-   Events can carry data between components
-   Example:
    ```php
    #[On('party-selected')]
    public function updateSelectedParty($partyId): void
    {
        $this->selectedParty = $partyId;
    }
    ```

### 2. Component Communication

-   Components can communicate through events
-   Use `dispatch()` instead of the older `emit()`
-   Events bubble up by default
-   Example:

    ```php
    // Dispatching
    $this->dispatch('event-name', param: 'value');

    // Listening
    #[On('event-name')]
    public function handleEvent($param) { }
    ```

### 3. Best Practices

-   Keep components focused and single-responsibility
-   Use computed properties for derived state
-   Leverage lifecycle hooks (mount, updating, updated)
-   Consider polling interval for real-time updates

## Common Gotchas and Solutions

### 1. Component Registration

-   Always update both the component class and AdminPanelProvider
-   Check for naming consistency across all files
-   Verify view template paths match the widget configuration

### 2. State Management

-   Be careful with global state changes (like setting active records)
-   Consider the impact on other components
-   Use events for cross-component communication

### 3. Form Handling

-   Forms need explicit submission handling
-   Live updates require the `live()` method
-   Form state is managed through the `data` array

### 4. Performance Considerations

-   Use polling intervals judiciously
-   Consider lazy loading for resource-intensive operations
-   Cache frequently accessed data when appropriate

## Real World Examples

### Active Party Selection

```php
class ActivePartySelector extends Widget implements HasForms
{
    protected static string $view = 'filament.widgets.active-party-selector';

    public function afterStateUpdated($state)
    {
        // Deactivate all parties
        Party::query()->update(['is_active' => false]);

        // Activate selected party
        if ($state) {
            Party::find($state)->update(['is_active' => true]);
        }
    }
}
```

### RSVP Table Filtering

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->when(
            Party::where('is_active', true)->exists(),
            fn ($q) => $q->whereHas('party', fn ($q) => $q->where('is_active', true))
        ));
}
```
