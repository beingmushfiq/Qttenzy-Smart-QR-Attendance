import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../store/authStore';
import apiClient from '../services/api/client';
import Layout from '../components/common/Layout';
import { toast } from 'react-toastify';
import { Plus, Edit, Trash2, Search, X } from 'lucide-react';

const OrgUsers = () => {
    const { user } = useAuthStore();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        password: '',
        role: 'student',
        phone: ''
    });
    const [editingId, setEditingId] = useState(null);

    const fetchUsers = async () => {
        try {
            setLoading(true);
            const response = await apiClient.get('/users', {
                params: { search }
            });
            // Handle pagination data structure or direct array
            const data = response.data?.data || response.data || [];
            if (Array.isArray(data)) {
                setUsers(data);
            } else if (data.data && Array.isArray(data.data)) {
                setUsers(data.data);
            } else {
                 setUsers([]);
            }
        } catch (error) {
            console.error('Error fetching users:', error);
            toast.error('Failed to load users');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUsers();
    }, [search]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingId) {
                await apiClient.put(`/users/${editingId}`, formData);
                toast.success('User updated successfully');
            } else {
                await apiClient.post('/users', formData);
                toast.success('User created successfully');
            }
            setShowModal(false);
            setFormData({ name: '', email: '', password: '', role: 'student', phone: '' });
            setEditingId(null);
            fetchUsers();
        } catch (error) {
            console.error('Error saving user:', error);
            toast.error(error.response?.data?.message || 'Failed to save user');
        }
    };

    const handleEdit = (user) => {
        setEditingId(user.id);
        setFormData({
            name: user.name,
            email: user.email,
            password: '', // Empty for security
            role: user.roles?.[0]?.name || user.role || 'student',
            phone: user.phone || ''
        });
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to delete this user?')) {
            try {
                await apiClient.delete(`/users/${id}`);
                toast.success('User deleted successfully');
                fetchUsers();
            } catch (error) {
                console.error('Error deleting user:', error);
                toast.error('Failed to delete user');
            }
        }
    };

    return (
        <Layout>
            <div className="p-4 sm:p-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h1 className="text-2xl sm:text-3xl font-bold">Organization Users</h1>
                    <button 
                        onClick={() => { setEditingId(null); setFormData({ name: '', email: '', password: '', role: 'student', phone: '' }); setShowModal(true); }}
                        className="bg-premium-primary text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-600 transition text-sm sm:text-base whitespace-nowrap"
                    >
                        <Plus size={20} /> Add User
                    </button>
                </div>

                <div className="mb-6 relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                    <input 
                        type="text" 
                        placeholder="Search users..." 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-white text-sm sm:text-base"
                    />
                </div>

                <div className="bg-white/5 rounded-2xl border border-white/10 overflow-hidden">
                    <div className="overflow-x-auto -mx-4 sm:-mx-6">
                        <div className="inline-block min-w-full align-middle px-4 sm:px-6">
                            <table className="min-w-full text-left">
                        <thead className="bg-white/5 text-gray-400">
                            <tr>
                                <th className="p-4">Name</th>
                                <th className="p-4">Email</th>
                                <th className="p-4">Role</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-white/5 text-gray-300">
                            {users.map(user => (
                                <tr key={user.id} className="hover:bg-white/5 transition">
                                    <td className="p-4 font-medium text-white">{user.name}</td>
                                    <td className="p-4">{user.email}</td>
                                    <td className="p-4 capitalize">{user.roles?.[0]?.name || user.role}</td>
                                    <td className="p-4">
                                        <span className={`px-2 py-1 rounded-full text-xs ${user.is_active ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'}`}>
                                            {user.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="p-4 text-right">
                                        <div className="flex justify-end gap-2">
                                            <button 
                                                onClick={() => handleEdit(user)}
                                                className="p-2 text-blue-400 hover:bg-blue-500/20 rounded-lg transition"
                                            >
                                                <Edit size={18} />
                                            </button>
                                            <button 
                                                onClick={() => handleDelete(user.id)}
                                                className="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {users.length === 0 && !loading && (
                                <tr>
                                    <td colSpan="5" className="p-8 text-center text-gray-500">No users found</td>
                                </tr>
                            )}
                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* Modal */}
                {showModal && (
                    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <div className="bg-gray-900 border border-white/10 rounded-2xl p-4 sm:p-6 w-full max-w-md shadow-2xl">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg sm:text-xl font-bold">{editingId ? 'Edit User' : 'Add User'}</h3>
                                <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-white">
                                    <X size={24} />
                                </button>
                            </div>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-400 mb-1">Name</label>
                                    <input 
                                        type="text" 
                                        value={formData.name}
                                        onChange={(e) => setFormData({...formData, name: e.target.value})}
                                        className="w-full px-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-sm sm:text-base"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-400 mb-1">Email</label>
                                    <input 
                                        type="email" 
                                        value={formData.email}
                                        onChange={(e) => setFormData({...formData, email: e.target.value})}
                                        className="w-full px-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-sm sm:text-base"
                                        required
                                    />
                                </div>
                                {!editingId && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-400 mb-1">Password</label>
                                        <input 
                                            type="password" 
                                            value={formData.password}
                                            onChange={(e) => setFormData({...formData, password: e.target.value})}
                                            className="w-full px-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-sm sm:text-base"
                                            required={!editingId}
                                            minLength="6"
                                            placeholder="Min 6 characters"
                                        />
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-400 mb-1">Role</label>
                                    <select 
                                        value={formData.role}
                                        onChange={(e) => setFormData({...formData, role: e.target.value})}
                                        className="w-full px-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-gray-300 text-sm sm:text-base"
                                    >
                                        <option value="student">Student</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="organization_admin">Admin</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-400 mb-1">Phone</label>
                                    <input 
                                        type="text" 
                                        value={formData.phone}
                                        onChange={(e) => setFormData({...formData, phone: e.target.value})}
                                        className="w-full px-4 py-2.5 sm:py-2 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-premium-primary text-sm sm:text-base"
                                    />
                                </div>
                                <button type="submit" className="w-full bg-premium-primary text-white py-2.5 sm:py-2 rounded-xl font-semibold hover:bg-blue-600 transition mt-4 text-sm sm:text-base">
                                    {editingId ? 'Update User' : 'Create User'}
                                </button>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default OrgUsers;
