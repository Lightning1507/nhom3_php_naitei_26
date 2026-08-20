import { Link } from 'react-router-dom';

export default function Header() {
    return (
        <header className="bg-white border-b border-gray-200 px-10 flex h-20 items-center justify-between shrink-0">
            <Link to="/" className="flex items-center gap-4">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h1 className="text-base font-bold text-gray-900 leading-tight">GovServices</h1>
                    <p className="text-xs text-gray-500 leading-tight">Citizen Portal</p>
                </div>
            </Link>
            <nav className="hidden md:flex items-center gap-2">
                <Link to="/" className="px-5 py-2.5 text-[16px] font-semibold text-gray-500 hover:bg-gray-100 rounded-xl transition">Home</Link>
                <Link to="/services" className="px-5 py-2.5 text-[16px] font-semibold text-white bg-blue-600 rounded-xl transition">All Services</Link>
                <Link to="/applications" className="px-5 py-2.5 text-[16px] font-semibold text-gray-500 hover:bg-gray-100 rounded-xl transition">Track Application</Link>
            </nav>
            <div>
                <Link to="/login" className="px-7 py-3 text-[17px] font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">Login</Link>
            </div>
        </header>
    );
}
