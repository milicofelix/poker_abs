import React from 'react';

export default function LastActionAlert({ action }) {
    if (! action) {
        return null;
    }

    return (
        <div className="rounded-2xl border border-amber-300/30 bg-amber-500/10 p-4 text-amber-100">
            {action.message}
        </div>
    );
}
