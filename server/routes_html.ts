import { Router } from 'express';
import fs from 'fs';
import path from 'path';

export const htmlRouter = Router();

export function compilePhpTemplate(html: string, role: string, user_id: number): string {
  // Strip top php block if any
  html = html.replace(/^<\?php[\s\S]*?\?>/, '');

  // Perform simple echo replacements
  html = html.replaceAll('<?php echo $role; ?>', role);
  html = html.replaceAll("<?php echo $role; ?>", role);
  html = html.replaceAll("<?php echo str_replace('_', ' ', $role); ?>", role.replace(/_/g, ' '));
  html = html.replaceAll('<?php echo str_replace(\'_\', \' \', $role); ?>', role.replace(/_/g, ' '));
  html = html.replaceAll('<?php echo substr($role, 0, 1); ?>', role.charAt(0));
  html = html.replaceAll('<?php echo $user_id; ?>', String(user_id));

  const lines = html.split('\n');
  const resultLines: string[] = [];
  
  function evaluatePhpCondition(cond: string): boolean {
    cond = cond.trim();
    const sanitizedRole = role.replace(/'/g, "\\'");
    
    if (cond.includes('in_array')) {
       // Example: in_array($role, ['Branch Manager', 'Kitchen Staff'])
       const match = cond.match(/in_array\(\s*\$role\s*,\s*\[([\s\S]+?)\]\)/);
       if (match) {
         const items = match[1].split(',').map(s => s.trim().replace(/['"]/g, ''));
         return items.includes(role);
       }
    }
    
    // Evaluate standard JS representation
    let jsCond = cond
      .replace(/\$role/g, `'${sanitizedRole}'`)
      .replace(/===/g, '==')
      .replace(/!==/g, '!=');
      
    try {
      return new Function(`return (${jsCond})`)();
    } catch (e) {
      console.error('Error evaluating condition:', cond, e);
      return false;
    }
  }

  const stack: { conditionMatched: boolean; currentBranchActive: boolean; elseSeen: boolean; parentActive: boolean }[] = [];
  let isWriting = true;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimLine = line.trim();

    // Check for IF block
    const ifMatch = trimLine.match(/<\?php\s*if\s*\(([\s\S]+?)\)\s*:\s*\?>/);
    if (ifMatch) {
      const cond = ifMatch[1];
      const parentActive = isWriting;
      const matched = parentActive && evaluatePhpCondition(cond);
      stack.push({
        conditionMatched: matched,
        currentBranchActive: matched,
        elseSeen: false,
        parentActive: parentActive
      });
      isWriting = matched;
      continue;
    }

    // Check for ELSEIF block
    const elseifMatch = trimLine.match(/<\?php\s*elseif\s*\(([\s\S]+?)\)\s*:\s*\?>/);
    if (elseifMatch) {
      const cond = elseifMatch[1];
      const currentBlock = stack[stack.length - 1];
      if (currentBlock) {
        if (!currentBlock.conditionMatched && currentBlock.parentActive && evaluatePhpCondition(cond)) {
          currentBlock.conditionMatched = true;
          currentBlock.currentBranchActive = true;
        } else {
          currentBlock.currentBranchActive = false;
        }
        isWriting = currentBlock.currentBranchActive;
      }
      continue;
    }

    // Check for ELSE block
    const elseMatch = trimLine.match(/<\?php\s*else\s*:\s*\?>/);
    if (elseMatch) {
      const currentBlock = stack[stack.length - 1];
      if (currentBlock) {
        currentBlock.elseSeen = true;
        if (!currentBlock.conditionMatched && currentBlock.parentActive) {
          currentBlock.currentBranchActive = true;
        } else {
          currentBlock.currentBranchActive = false;
        }
        isWriting = currentBlock.currentBranchActive;
      }
      continue;
    }

    // Check for ENDIF block
    const endifMatch = trimLine.match(/<\?php\s*endif\s*(?:;\s*)?\?>/);
    if (endifMatch) {
      stack.pop();
      const parentBlock = stack[stack.length - 1];
      isWriting = parentBlock ? parentBlock.currentBranchActive : true;
      continue;
    }

    if (isWriting) {
      resultLines.push(line);
    }
  }

  return resultLines.join('\n');
}

// Routes
htmlRouter.get('/', (req: any, res) => {
  if (req.session?.user_id) {
    return res.redirect('/dashboard.php');
  }
  const indexPage = path.join(process.cwd(), 'index.php');
  res.sendFile(indexPage);
});

htmlRouter.get('/index.php', (req: any, res) => {
  if (req.session?.user_id) {
     return res.redirect('/dashboard.php');
  }
  const indexPage = path.join(process.cwd(), 'index.php');
  res.sendFile(indexPage);
});

htmlRouter.get('/index.html', (req: any, res) => {
  res.redirect('/index.php');
});

htmlRouter.get('/dashboard.php', (req: any, res) => {
  if (!req.session?.user_id) {
    return res.redirect('/index.php');
  }

  const role = req.session.role || 'Customer';
  const userId = req.session.user_id;

  try {
     const dashboardPath = path.join(process.cwd(), 'dashboard.php');
     const template = fs.readFileSync(dashboardPath, 'utf8');
     const compiled = compilePhpTemplate(template, role, userId);
     res.send(compiled);
  } catch (err: any) {
     res.status(500).send('Error compiling Dashboard: ' + err.message);
  }
});
