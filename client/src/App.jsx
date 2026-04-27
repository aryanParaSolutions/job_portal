import { useState } from "react";

import {
  BrowserRouter as Router,
  Routes,
  Route,
  Navigate,
  Outlet,
} from "react-router-dom";

import Authentication from "./pages/authentication/Authentication";

import AdminLayout from "./layouts/AdminLayout";
import AdminDashboard from "./pages/admin/AdminDashboard";
import AdminProfile from "./pages/admin/AdminProfile";

import EmployerLayout from "./layouts/EmployerLayout";
import EmployerDashboard from "./pages/employer/EmployerDashboard";
import EmployerProfile from "./pages/employer/EmployerProfile";

import CandidateLayout from "./layouts/CandidateLayout";
import CandidateDashboard from "./pages/candidate/CandidateDashboard";
import CandidateProfile from "./pages/candidate/CandidateProfile";

const user = true;

function UnauthenticatedLayout() {
  return user ? <Navigate to="/admin" /> : <Outlet />;
}

function AuthenticatedLayout({ roles }) {
  const userRole = "employer";

  if (!user) return <Navigate to="/auth" />;

  if (!roles?.includes(userRole)) return <Navigate to="/unauthorized" />;

  return <Outlet />;
}

export default function App() {
  return (
    <Router>
      <Routes>
        <Route element={<UnauthenticatedLayout />}>
          <Route path="/auth" element={<Authentication />} />
        </Route>

        {/* Admin Routes */}
        <Route element={<AuthenticatedLayout roles={["admin"]} />}>
          <Route path="/admin" element={<AdminLayout />}>
            <Route index element={<AdminDashboard />} />
            <Route path="settings" element={<AdminProfile />} />
          </Route>
        </Route>

        {/* Employer Routes */}
        <Route element={<AuthenticatedLayout roles={["employer"]} />}>
          <Route path="/employer" element={<EmployerLayout />}>
            <Route index element={<EmployerDashboard />} />
            <Route path="settings" element={<EmployerProfile />} />
          </Route>
        </Route>

        {/* Candidate Routes */}
        <Route element={<AuthenticatedLayout roles={["candidate"]} />}>
          <Route path="/candidate" element={<CandidateLayout />}>
            <Route index element={<CandidateDashboard />} />
            <Route path="settings" element={<CandidateProfile />} />
          </Route>
        </Route>

        {/* Default redirect */}
        <Route path="/" element={<Navigate to="/admin" />} />
      </Routes>
    </Router>
  );
}
