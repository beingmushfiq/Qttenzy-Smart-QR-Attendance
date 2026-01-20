import { Routes, Route } from 'react-router-dom';
import SessionList from '../components/session/SessionList';
import SessionDetail from '../components/session/SessionDetail';
import SessionCreate from '../components/session/SessionCreate';

const Sessions = () => {
  return (
    <Routes>
      <Route path="/" element={<SessionList />} />
      <Route path="/create" element={<SessionCreate />} />
      <Route path="/:id" element={<SessionDetail />} />
    </Routes>
  );
};

export default Sessions;

