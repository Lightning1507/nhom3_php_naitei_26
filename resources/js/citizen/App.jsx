import { Route, Routes } from 'react-router-dom';

import HomePage from './pages/HomePage';
import CompleteGoogleRegistrationPage from './pages/CompleteGoogleRegistrationPage';
import LoginPage from './pages/LoginPage';
import ProfilePage from './pages/ProfilePage';
import RegisterPage from './pages/RegisterPage';
import ServiceCatalog from './pages/ServiceCatalog';
import ServiceDetail from './pages/ServiceDetail';

export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/auth/google/complete" element={<CompleteGoogleRegistrationPage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/services" element={<ServiceCatalog />} />
            <Route path="/services/:id" element={<ServiceDetail />} />
            <Route path="*" element={<HomePage />} />
        </Routes>
    );
}
