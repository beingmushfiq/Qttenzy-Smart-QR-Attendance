import { useEffect, useState } from 'react';
import { attendanceAPI } from '../services/api/attendance';
import { toast } from 'react-toastify';
import GlassCard from '../components/common/GlassCard';
import AttendanceScanner from '../components/attendance/AttendanceScanner';

const Attendance = () => {
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showScanner, setShowScanner] = useState(false);

  useEffect(() => {
    fetchHistory();
  }, []);

  const fetchHistory = async () => {
    try {
      setLoading(true);
      const response = await attendanceAPI.getHistory();
      console.log('Attendance history response:', response);
      // Handle response format - data might be nested
      const historyData = response.data?.data || response.data || response || [];
      console.log('Extracted history data:', historyData);
      setHistory(Array.isArray(historyData) ? historyData : []);
    } catch (error) {
      console.error('Error fetching attendance history:', error);
      toast.error('Failed to load attendance history');
    } finally {
      setLoading(false);
    }
  };

  const handleAttendanceMarked = () => {
    setShowScanner(false);
    fetchHistory(); // Refresh history after marking attendance
    toast.success('Attendance marked successfully!');
  };

  if (showScanner) {
    return <AttendanceScanner onClose={() => setShowScanner(false)} onSuccess={handleAttendanceMarked} />;
  }

  if (loading) {
    return <div className="text-center p-8">Loading attendance history...</div>;
  }

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <h1 className="text-4xl font-extrabold text-white mb-2 tracking-tight">Attendance</h1>
          <p className="text-white/40 font-medium tracking-tight">Scan QR and mark your presence instantly</p>
        </div>
        <button
          onClick={() => setShowScanner(true)}
          className="bg-gradient-premium text-white px-8 py-3.5 rounded-2xl font-bold shadow-lg shadow-premium-primary/20 hover:scale-[1.02] transition-all whitespace-nowrap flex items-center gap-2"
        >
          <span className="text-xl">📷</span>
          Mark Attendance
        </button>
      </div>
      
      <GlassCard className="overflow-hidden border border-white/5 !p-0">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-white/5">
            <thead className="bg-white/5">
              <tr>
                <th className="px-8 py-5 text-left text-xs font-black text-white/30 uppercase tracking-widest">Session</th>
                <th className="px-8 py-5 text-left text-xs font-black text-white/30 uppercase tracking-widest">Date & Time</th>
                <th className="px-8 py-5 text-left text-xs font-black text-white/30 uppercase tracking-widest">Status</th>
                <th className="px-8 py-5 text-left text-xs font-black text-white/30 uppercase tracking-widest">Match Score</th>
                <th className="px-8 py-5 text-left text-xs font-black text-white/30 uppercase tracking-widest">GPS Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {history.map((attendance) => (
                <tr key={attendance.id} className="hover:bg-white/[0.02] transition-colors group">
                  <td className="px-8 py-6 whitespace-nowrap">
                    <div className="text-sm font-bold text-white group-hover:text-premium-primary transition-colors">
                      {attendance.session?.title || 'N/A'}
                    </div>
                  </td>
                  <td className="px-8 py-6 whitespace-nowrap text-sm text-white/40 font-medium">
                    {new Date(attendance.verified_at).toLocaleString()}
                  </td>
                  <td className="px-8 py-6 whitespace-nowrap">
                    <span className={`px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-xl border ${
                      attendance.status === 'verified' || attendance.status === 'present'
                        ? 'bg-premium-accent/10 text-premium-accent border-premium-accent/20' 
                        : 'bg-white/5 text-white/40 border-white/10'
                    }`}>
                      {attendance.status}
                    </span>
                  </td>
                  <td className="px-8 py-6 whitespace-nowrap">
                    <div className="flex items-center gap-2">
                       <div className="w-16 h-1.5 bg-white/5 rounded-full overflow-hidden">
                          <div 
                            className="h-full bg-gradient-premium" 
                            style={{ width: `${Number(attendance.face_match_score) || 0}%` }}
                          ></div>
                       </div>
                       <span className="text-sm font-bold text-white/80">{Number(attendance.face_match_score || 0).toFixed(1)}%</span>
                    </div>
                  </td>
                  <td className="px-8 py-6 whitespace-nowrap">
                    {attendance.gps_valid ? (
                      <span className="flex items-center gap-1.5 text-xs font-bold text-premium-accent">
                        <span className="w-1.5 h-1.5 rounded-full bg-premium-accent shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                        Valid
                      </span>
                    ) : (
                      <span className="flex items-center gap-1.5 text-xs font-bold text-red-400">
                        <span className="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                        Invalid
                      </span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {history.length === 0 && (
            <div className="text-center py-20">
              <div className="text-4xl mb-4">📭</div>
              <p className="text-white/20 font-bold uppercase tracking-widest text-sm">No records found</p>
              <p className="text-white/10 text-xs mt-2">Click "Mark Attendance" to scan a QR code</p>
            </div>
          )}
        </div>
      </GlassCard>
    </div>
  );
};

export default Attendance;

