import { useEffect, useState } from 'react';

import apiClient from '../api/client';

export default function HomePage() {
    const [apiStatus, setApiStatus] = useState('Checking API connection...');

    useEffect(() => {
        apiClient
            .get('/health')
            .then(({ data }) => setApiStatus(data.message))
            .catch(() => setApiStatus('API is unavailable'));
    }, []);

    return (
        <main className="min-h-screen bg-slate-50 px-6 py-16 text-slate-900">
            <section className="mx-auto max-w-4xl rounded-2xl bg-white p-10 shadow-sm ring-1 ring-slate-200">
                <p className="text-sm font-semibold uppercase tracking-widest text-sky-700">
                    Citizen site
                </p>
                <h1 className="mt-3 text-4xl font-bold tracking-tight">
                    Public Service Management System
                </h1>
                <p className="mt-4 max-w-2xl text-lg text-slate-600">
                    This React application is the citizen-facing interface and communicates with
                    the versioned Laravel REST API.
                </p>

                <div className="mt-8 inline-flex items-center gap-3 rounded-full bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">
                    <span className="size-2 rounded-full bg-emerald-500" aria-hidden="true" />
                    {apiStatus}
                </div>
            </section>
        </main>
    );
}
