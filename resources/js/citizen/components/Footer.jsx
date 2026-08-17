export default function Footer({ className = "" }) {
    return (
        <footer className={`border-t border-gray-200 py-5 text-center flex items-center justify-center text-[14px] text-gray-400 gap-2 ${className}`}>
            <p>© 2026 GovServices · All rights reserved · <span className="text-blue-600">Privacy Policy</span></p>
            <span className="text-gray-200">|</span>
            <a href="/admin/login" className="font-medium text-gray-500 hover:text-gray-900">Staff / Admin Portal →</a>
        </footer>
    );
}
