import React from 'react';

const GlassCard = ({ children, className = '', hover = true }) => {
  return (
    <div className={`
      glass rounded-2xl p-4 sm:p-6 
      ${hover ? 'hover:scale-[1.02] transition-transform duration-300 ease-out cursor-default' : ''} 
      ${className}
    `}>
      {children}
    </div>
  );
};

export default GlassCard;
