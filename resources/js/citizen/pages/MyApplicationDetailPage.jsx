import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';

import {
    deleteApplicationDocument,
    downloadApplicationDocument,
    fetchApplication,
    uploadApplicationDocument,
} from '../api/applications';
import { forgetCitizenSession, getApiError } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';
import DocumentUploader from '../components/DocumentUploader';
import Footer from '../components/Footer';
import Header from '../components/Header';
import StatusBadge from '../components/StatusBadge';
import { formatBytes, formatDateTime } from '../utils/format';
import { normalizeDocumentRequirements } from '../utils/schema';

export default function MyApplicationDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const location = useLocation();

    const [application, setApplication] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [flash, setFlash] = useState(location.state?.flash ?? '');
    const [message, setMessage] = useState('');
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [deletingId, setDeletingId] = useState(null);

    const isEditable = application?.status === 'received';
    const canUpload = application?.status === 'received' || application?.status === 'supplement_required';

    async function loadApplication() {
        setLoadError(false);

        try {
            const response = await fetchApplication(id);
            setApplication(response.data);
        } catch (error) {
            if (error?.response?.status === 401) {
                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: 'Vui lòng đăng nhập để xem hồ sơ.' },
                });

                return;
            }

            if (error?.response?.status === 403 || error?.response?.status === 404) {
                navigate('/applications', { replace: true });

                return;
            }

            setLoadError(true);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        let isMounted = true;

        async function boot() {
            try {
                await fetchCitizenProfile();
            } catch {
                if (!isMounted) {
                    return;
                }

                forgetCitizenSession();
                navigate('/login', {
                    replace: true,
                    state: { flash: 'Vui lòng đăng nhập để xem hồ sơ.' },
                });

                return;
            }

            if (isMounted) {
                await loadApplication();
            }
        }

        boot();

        return () => {
            isMounted = false;
        };
    }, [id, navigate]);

    useEffect(() => {
        if (!flash) {
            return undefined;
        }

        const timeout = window.setTimeout(() => setFlash(''), 6000);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    async function handleUpload() {
        if (files.length === 0) {
            return;
        }

        setMessage('');
        setUploading(true);

        try {
            for (const entry of files) {
                await uploadApplicationDocument(id, entry.file, entry.requirementCode || undefined);
            }

            setFiles([]);
            await loadApplication();
        } catch (error) {
            setMessage(getApiError(error).message);
        } finally {
            setUploading(false);
        }
    }

    async function handleDelete(documentId) {
        setDeletingId(documentId);
        setMessage('');

        try {
            await deleteApplicationDocument(id, documentId);
            await loadApplication();
        } catch (error) {
            setMessage(getApiError(error).message);
        } finally {
            setDeletingId(null);
        }
    }

    function filesForCode(code) {
        return files.filter((entry) => entry.requirementCode === code);
    }

    function addFiles(code, fileList) {
        setFiles((current) => [
            ...current,
            ...Array.from(fileList).map((file) => ({ requirementCode: code, file })),
        ]);
    }

    function removeFile(entry) {
        setFiles((current) => current.filter((item) => item !== entry));
    }

    if (loading) {
        return (
            <main className="min-h-screen bg-surface flex flex-col font-sans">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex items-center justify-center py-20 text-gray-500">
                    Đang tải...
                </div>
                <Footer />
            </main>
        );
    }

    if (loadError || !application) {
        return (
            <main className="min-h-screen bg-surface flex flex-col font-sans">
                <Header />
                <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col items-center justify-center py-20">
                    <p className="text-gray-600">Không thể tải chi tiết hồ sơ.</p>
                    <button type="button" className="mt-4 text-sm font-semibold text-primary hover:underline" onClick={() => { setLoading(true); loadApplication(); }}>
                        Thử lại
                    </button>
                </div>
                <Footer />
            </main>
        );
    }

    const formEntries = Object.entries(application.form_data ?? {});
    const documents = application.documents ?? [];
    const missingDocs = application.missing_required_documents ?? [];

    const serviceRequirements = normalizeDocumentRequirements({
        document_requirements: application.service_type?.document_requirements,
    });
    const requirementByCode = Object.fromEntries(serviceRequirements.map((requirement) => [requirement.code, requirement]));

    const documentGroups = [];

    documents.forEach((document) => {
        const label = document.requirement_label || 'Tài liệu khác';
        let group = documentGroups.find((item) => item.label === label);

        if (!group) {
            group = { label, code: document.requirement_code, items: [] };
            documentGroups.push(group);
        }

        group.items.push(document);
    });

    const missingSlots = missingDocs.map((missing) => (
        requirementByCode[missing.code] ?? { code: missing.code, label: missing.label, type: 'mixed', required: true }
    ));

    return (
        <main className="min-h-screen bg-surface flex flex-col font-sans">
            <Header />

            <div className="flex-1 w-full max-w-[1101px] mx-auto bg-white border-x border-gray-200 flex flex-col">
                <div className="px-10 py-6 border-b border-gray-100">
                    <Link className="flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-blue-600 transition" to="/applications">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" /></svg>
                        Quay lại hồ sơ của tôi
                    </Link>
                </div>

                <div className="flex-1 px-10 py-8">
                    {flash && (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                            {flash}
                        </div>
                    )}

                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-consolas text-sm text-gray-500">{application.application_code}</p>
                            <h1 className="mt-1 text-[26px] font-bold tracking-tight text-gray-900">
                                {application.service_type?.name ?? 'Dịch vụ'}
                            </h1>
                        </div>
                        <StatusBadge status={application.status} />
                    </div>

                    <div className="mt-8 grid gap-4 sm:grid-cols-2">
                        <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6">
                            <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">Ngày nộp</h3>
                            <p className="mt-1 text-lg font-bold text-gray-900">{formatDateTime(application.submitted_at)}</p>
                        </div>
                        <div className="rounded-2xl border-[1.5px] border-gray-100 bg-gray-50 p-6">
                            <h3 className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">Trạng thái</h3>
                            <p className="mt-1 text-lg font-bold text-gray-900">{application.status}</p>
                        </div>
                    </div>

                    {formEntries.length > 0 && (
                        <section className="mt-8">
                            <h2 className="mb-4 text-[18px] font-bold text-gray-900">Thông tin đã khai</h2>
                            <div className="rounded-2xl border-[1.5px] border-gray-100 bg-white p-6">
                                <dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                    {formEntries.map(([key, value]) => (
                                        <div key={key}>
                                            <dt className="text-[13px] font-semibold text-gray-400 uppercase tracking-widest">{key}</dt>
                                            <dd className="mt-1 text-[15px] font-medium text-gray-900 break-words">{String(value ?? '—')}</dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        </section>
                    )}

                    {missingDocs.length > 0 && (application.status === 'received' || application.status === 'supplement_required') && (
                        <div className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                            Thiếu {missingDocs.length} tài liệu bắt buộc: {missingDocs.map((doc) => doc.label).join(', ')}. Vui lòng tải lên để hồ sơ được xử lý.
                        </div>
                    )}

                    <section className="mt-8">
                        <h2 className="mb-4 text-[18px] font-bold text-gray-900">Tài liệu đính kèm</h2>

                        {documentGroups.length === 0 ? (
                            <p className="rounded-xl border border-gray-100 bg-gray-50 p-5 text-sm text-gray-600">
                                Chưa có tài liệu nào.
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {documentGroups.map((group) => (
                                    <div key={group.label}>
                                        <p className="mb-2 text-[13px] font-semibold uppercase tracking-widest text-gray-400">
                                            {group.label}
                                            {group.code && <span className="ml-1 normal-case tracking-normal text-gray-400">· {group.code}</span>}
                                        </p>
                                        <ul className="border-[1.5px] border-gray-200 rounded-2xl overflow-hidden">
                                            {group.items.map((document, index) => (
                                                <li key={document.id} className={`flex items-center justify-between gap-4 p-5 ${index !== 0 ? 'border-t-[1.5px] border-gray-100' : ''}`}>
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] text-primary">
                                                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                        </div>
                                                        <div className="min-w-0">
                                                            <p className="truncate text-[15px] font-semibold text-gray-900">{document.original_name}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {formatBytes(document.file_size)} · {formatDateTime(document.created_at)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        <button
                                                            type="button"
                                                            className="rounded-lg px-4 py-2 text-sm font-semibold text-primary transition hover:bg-blue-50"
                                                            onClick={() => downloadApplicationDocument(id, document.id, document.original_name)}
                                                        >
                                                            Tải xuống
                                                        </button>
                                                        {isEditable && (
                                                            <button
                                                                type="button"
                                                                disabled={deletingId === document.id}
                                                                className="rounded-lg px-4 py-2 text-sm font-semibold text-danger transition hover:bg-red-50 disabled:opacity-50"
                                                                onClick={() => handleDelete(document.id)}
                                                            >
                                                                {deletingId === document.id ? 'Đang xóa...' : 'Xóa'}
                                                            </button>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {canUpload && (
                        <section className="mt-8">
                            <h2 className="mb-4 text-[18px] font-bold text-gray-900">Tải thêm tài liệu</h2>

                            {missingSlots.length > 0 ? (
                                <div className="space-y-6">
                                    {missingSlots.map((requirement) => (
                                        <div key={requirement.code} className="rounded-2xl border border-gray-200 bg-white p-5">
                                            <p className="mb-3 text-[15px] font-semibold text-gray-900">
                                                {requirement.label}
                                                <span className="ml-1 text-danger">*</span>
                                            </p>
                                            <DocumentUploader
                                                requirement={requirement}
                                                files={filesForCode(requirement.code)}
                                                onAdd={(next) => addFiles(requirement.code, next)}
                                                onRemove={(file) => removeFile(filesForCode(requirement.code).find((entry) => entry.file === file))}
                                            />
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <DocumentUploader
                                    requirement={null}
                                    files={filesForCode('')}
                                    onAdd={(next) => addFiles('', next)}
                                    onRemove={(file) => removeFile(filesForCode('').find((entry) => entry.file === file))}
                                />
                            )}

                            {files.length > 0 && (
                                <div className="mt-4 flex justify-end">
                                    <button
                                        type="button"
                                        disabled={uploading}
                                        className="btn-primary rounded-xl px-7 py-3 text-[15px]"
                                        onClick={handleUpload}
                                    >
                                        {uploading ? 'Đang tải lên...' : 'Tải lên tài liệu'}
                                    </button>
                                </div>
                            )}
                        </section>
                    )}

                    {message && (
                        <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">{message}</p>
                    )}
                </div>

                <Footer />
            </div>
        </main>
    );
}