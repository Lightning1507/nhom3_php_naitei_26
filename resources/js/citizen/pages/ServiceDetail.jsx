import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { fetchService } from '../api/services';
import Header from '../components/Header';
import Footer from '../components/Footer';

export default function ServiceDetail() {
    const { id } = useParams();
    const [service, setService] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let isMounted = true;
        fetchService(id)
            .then((res) => {
                if (isMounted) {
                    setService(res.data.data);
                    setLoading(false);
                }
            })
            .catch(() => {
                if (isMounted) setLoading(false);
            });
        return () => {
            isMounted = false;
        };
    }, [id]);

    return (
        <main className="min-h-screen bg-[#F9FAFB] flex flex-col font-sans">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <div className="px-10 py-6 border-b border-gray-100">
                    <Link className="flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-blue-600 transition" to="/services">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to Services
                    </Link>
                </div>
                
                <div className="flex-1 px-10 py-8">
                    {loading ? (
                        <div className="py-10 text-center text-gray-500">Loading...</div>
                    ) : !service ? (
                        <div className="py-10 text-center text-gray-500">Service not found</div>
                    ) : (
                        <div className="max-w-4xl">
                            <div className="mb-8">
                                <span className="mb-4 inline-block rounded-full bg-[#E8F0FE] px-4 py-1.5 text-[13px] font-bold text-blue-700 uppercase tracking-wide">
                                    {service.category_name || 'Other'}
                                </span>
                                <h1 className="text-[32px] font-bold tracking-tight text-gray-900 leading-tight">
                                    {service.name}
                                </h1>
                            </div>

                            {!service.is_active && (
                                <div className="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700">
                                    <strong>Note:</strong> This service is currently suspended and not accepting new applications.
                                </div>
                            )}

                            <div className="mb-10 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6 flex items-center justify-between">
                                    <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">Processing Time</h3>
                                    <p className="text-xl font-bold text-gray-900">
                                        {service.processing_time_days} days
                                    </p>
                                </div>
                                <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6 flex items-center justify-between">
                                    <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">Fee</h3>
                                    <p className="text-xl font-bold text-blue-600">
                                        {service.fee > 0 ? `${new Intl.NumberFormat('en-US').format(service.fee)} ₫` : 'Free'}
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-10">
                                <section>
                                    <h2 className="mb-4 text-[20px] font-bold text-gray-900 flex items-center gap-2">
                                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Service Description
                                    </h2>
                                    <div className="text-[15px] text-gray-600 leading-relaxed">
                                        {service.description || 'No detailed description available.'}
                                    </div>
                                </section>

                                <section>
                                    <h2 className="mb-4 text-[20px] font-bold text-gray-900 flex items-center gap-2">
                                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Requirements & Conditions
                                    </h2>
                                    <div className="text-[15px] text-gray-600 leading-relaxed whitespace-pre-line bg-gray-50 border border-gray-100 p-5 rounded-xl">
                                        {service.requirements || 'No special requirements.'}
                                    </div>
                                </section>
                                
                                {(() => {
                                    let docs = [];
                                    try {
                                        docs = typeof service.document_requirements === 'string'
                                            ? JSON.parse(service.document_requirements)
                                            : (service.document_requirements || []);
                                    } catch(e) {}
                                    
                                    return docs.length > 0 && (
                                        <section>
                                            <h2 className="mb-4 text-[20px] font-bold text-gray-900 flex items-center gap-2">
                                                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                Document Requirements
                                            </h2>
                                            <ul className="text-[15px] text-gray-600 space-y-3 bg-gray-50 border border-gray-100 p-5 rounded-xl">
                                                {docs.map((doc, idx) => (
                                                    <li key={idx} className="flex gap-3">
                                                        <svg className="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                                        <span>{typeof doc === 'string' ? doc : (doc.label || doc.name || doc.code || 'Document')}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </section>
                                    );
                                })()}
                            </div>
                            
<div className="mt-12 flex justify-end border-t-[1.5px] border-gray-100 pt-8">
                                {service.is_active ? (
                                    <Link
                                        to={`/services/${service.id}/apply`}
                                        className="btn-primary rounded-xl px-8 py-3.5 text-[16px] font-semibold transition flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md"
                                    >
                                        Submit Application Online
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    </Link>
                                ) : (
                                    <button
                                        disabled
                                        className="btn-primary rounded-xl px-8 py-3.5 text-[16px] font-semibold transition flex items-center gap-2 bg-gray-200 text-gray-400 cursor-not-allowed"
                                    >
                                        Submit Application Online
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
                
                <Footer className="mt-auto" />
            </div>
        </main>
    );
}
