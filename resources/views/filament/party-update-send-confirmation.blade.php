<div class="space-y-4 text-sm">
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <dl class="grid gap-3">
            <div>
                <dt class="font-medium text-gray-500">Subject</dt>
                <dd class="mt-1 font-semibold text-gray-950">{{ $summary['subject'] }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Audience</dt>
                <dd class="mt-1 font-semibold text-gray-950">{{ $summary['audience'] }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Segment/tag</dt>
                <dd class="mt-1 font-semibold text-gray-950">{{ $summary['segment'] }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Mailchimp campaign</dt>
                <dd class="mt-1 font-semibold text-gray-950">{{ $summary['campaign'] }}</dd>
            </div>
        </dl>
    </div>

    <p class="text-gray-600">
        This will save your latest changes, create or update the Mailchimp campaign draft for this update, and send it now.
        This cannot be undone.
    </p>
</div>
